<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\Folder;
use App\Services\DocxTemplateService;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentAutomationController extends Controller
{
    private const CLIENT_FIELDS = [
        'nome' => 'name', 'documento' => 'document', 'identidade' => 'identity_document',
        'orgao_emissor' => 'identity_issuer', 'profissao' => 'profession',
        'endereco' => 'address', 'complemento' => 'address_complement', 'bairro' => 'district',
        'cidade' => 'city', 'cep' => 'postal_code', 'email' => 'email', 'telefone' => 'phone',
    ];

    public function __construct(private DocxTemplateService $docx) {}

    public function index(Request $request)
    {
        return response()->json(DocumentTemplate::query()
            ->when(! $request->boolean('include_archived'), fn ($query) => $query->whereNull('archived_at'))
            ->select(['id', 'name', 'fields', 'created_at', 'archived_at'])
            ->selectSub(DB::table('document_generations')->selectRaw('COUNT(*)')->whereColumn('document_template_id', 'document_templates.id'), 'generations_count')
            ->latest('id')->get());
    }

    public function store(Request $request)
    {
        return $this->import($request);
    }

    public function replace(Request $request, DocumentTemplate $template)
    {
        return $this->import($request, $template);
    }

    public function destroy(DocumentTemplate $template)
    {
        $archived = DB::transaction(function () use ($template) {
            $locked = DocumentTemplate::lockForUpdate()->findOrFail($template->id);
            if ($locked->archived_at || DB::table('document_generations')->where('document_template_id', $locked->id)->exists()) {
                $locked->update(['archived_at' => $locked->archived_at ?? now()]);

                return true;
            }
            // Delete the unused file first: on storage failure the database record remains.
            abort_if(Storage::disk('local')->exists($locked->path) && ! Storage::disk('local')->delete($locked->path), 500, 'Não foi possível excluir o arquivo do modelo.');
            $locked->delete();

            return false;
        });

        return response()->json(['archived' => $archived]);
    }

    private function import(Request $request, ?DocumentTemplate $previous = null)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'file' => 'required|file|max:10240|extensions:docx']);
        $file = $request->file('file');
        $fields = $this->docx->inspect($file->getRealPath());
        foreach ($fields as $field) {
            if (! array_key_exists($field, $this->catalog()) && ! preg_match('/^campo\.[a-z][a-z0-9_]{0,49}$/', $field)) {
                throw ValidationException::withMessages(['file' => "Campo desconhecido: {$field}. Use o catálogo ou campo.nome_do_campo."]);
            }
        }
        $path = $file->storeAs('organizations/'.app(CurrentOrganization::class)->id().'/document-templates', Str::uuid().'.docx', 'local');
        if (! $path) {
            abort(500, 'Não foi possível armazenar o modelo.');
        }
        try {
            $template = DB::transaction(function () use ($previous, $data, $request, $path, $fields, $file) {
                if ($previous) {
                    $previous = DocumentTemplate::lockForUpdate()->findOrFail($previous->id);
                    abort_if($previous->archived_at, 409, 'Este modelo já foi arquivado. Atualize a lista.');
                }
                $created = DocumentTemplate::create([
                    'name' => $data['name'], 'user_id' => $request->user()->id,
                    'path' => $path, 'fields' => $fields, 'sha256' => hash_file('sha256', $file->getRealPath()),
                ]);
                $previous?->update(['archived_at' => now()]);

                return $created;
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return response()->json($template, 201);
    }

    public function download(DocumentTemplate $template)
    {
        abort_unless(Storage::disk('local')->exists($template->path), 404);

        return Storage::disk('local')->download($template->path, (Str::slug($template->name) ?: 'modelo').'.docx');
    }

    private function catalog(): array
    {
        $fields = ['processo.numero' => 'Número do processo', 'pasta.nome' => 'Nome da pasta',
            'escritorio.nome' => 'Nome do escritório', 'data.hoje' => 'Data da geração',
            'cliente.estado_civil' => 'Estado civil do cliente', 'cliente.qualificacao' => 'Qualificação na pasta'];
        foreach (self::CLIENT_FIELDS as $key => $column) {
            $fields['cliente.'.$key] = 'Cliente: '.str_replace('_', ' ', $key);
        }

        return $fields;
    }

    public function fields()
    {
        return response()->json($this->catalog());
    }

    public function context(Folder $folder)
    {
        $folder->load('folderClients.client.maritalStatus', 'folderClients.qualification');

        return response()->json([
            'catalog' => $this->catalog(),
            'parties' => $folder->folderClients->map(fn ($link) => [
                'value' => $link->id, 'label' => $link->client->name.' — '.($link->qualification?->name ?? 'Sem qualificação'),
            ]),
        ]);
    }

    private function prepare(Request $request, Folder $folder): array
    {
        $data = $request->validate([
            'template_id' => 'required|integer', 'party_id' => 'nullable|integer',
            'values' => 'present|array|max:100', 'values.*' => ['nullable', 'string', 'max:20000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F]/'],
        ]);
        $template = DocumentTemplate::findOrFail($data['template_id']);
        abort_if($template->archived_at, 409, 'Este modelo foi arquivado. Selecione um modelo disponível e revise novamente.');
        $values = [
            'processo.numero' => $folder->process_number, 'pasta.nome' => $folder->name,
            'escritorio.nome' => app(CurrentOrganization::class)->get()->name,
            'data.hoje' => now('America/Sao_Paulo')->format('d/m/Y'),
        ];
        $party = null;
        if (! empty($data['party_id'])) {
            $party = $folder->folderClients()->with('client.maritalStatus', 'qualification')->findOrFail($data['party_id']);
        }
        if (collect($template->fields)->contains(fn ($f) => str_starts_with($f, 'cliente.')) && ! $party) {
            throw ValidationException::withMessages(['party_id' => 'Selecione o cliente vinculado à pasta.']);
        }
        foreach (self::CLIENT_FIELDS as $key => $column) {
            $values['cliente.'.$key] = $party?->client?->$column;
        }
        $values['cliente.estado_civil'] = $party?->client?->maritalStatus?->name;
        $values['cliente.qualificacao'] = $party?->qualification?->name;
        foreach ($data['values'] as $field => $value) {
            if (! in_array($field, $template->fields, true)) {
                throw ValidationException::withMessages(['values' => "Campo não pertence ao modelo: {$field}."]);
            }
            if ($value !== null && (! is_string($value) || mb_strlen($value) > 20000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value))) {
                throw ValidationException::withMessages(['values' => "Valor inválido para {$field}. Use texto de até 20.000 caracteres."]);
            }
            $values[$field] = $value;
        }
        $values = array_map(fn ($value) => (string) ($value ?? ''), array_intersect_key($values, array_flip($template->fields)));
        foreach ($template->fields as $field) {
            $values[$field] ??= '';
        }
        $path = Storage::disk('local')->path($template->path);
        abort_unless(is_file($path), 404);
        abort_unless(hash_equals($template->sha256, hash_file('sha256', $path)), 409, 'O arquivo do modelo foi alterado. Importe uma nova versão.');
        $rendered = $this->docx->render($path, $values);
        $missing = array_values(array_filter($rendered['required'], fn ($key) => trim($values[$key] ?? '') === ''));
        $signature = hash_hmac('sha256', json_encode([$folder->id, $template->id, $template->sha256, $values], JSON_UNESCAPED_UNICODE), config('app.key'));

        return compact('template', 'values', 'rendered', 'missing', 'signature', 'path');
    }

    public function preview(Request $request, Folder $folder)
    {
        $result = $this->prepare($request, $folder);

        return response()->json([
            'values' => $result['values'], 'missing' => $result['missing'],
            'text' => $result['rendered']['text'], 'signature' => $result['signature'],
        ]);
    }

    public function generate(Request $request, Folder $folder)
    {
        $request->validate(['name' => 'required|string|max:150', 'signature' => 'required|string|size:64']);
        $result = $this->prepare($request, $folder);
        if ($result['missing']) {
            throw ValidationException::withMessages(['values' => 'Complete os campos: '.implode(', ', $result['missing'])]);
        }
        abort_unless(hash_equals($result['signature'], $request->string('signature')->toString()), 409, 'Os dados mudaram. Revise a prévia novamente.');
        $disk = Storage::disk('local');
        $directory = 'organizations/'.app(CurrentOrganization::class)->id().'/folders/'.$folder->id.'/documents';
        $disk->makeDirectory($directory);
        $path = $directory.'/'.Str::uuid().'.docx';
        try {
            $this->docx->write($result['path'], $disk->path($path), $result['rendered']['parts']);
            $document = DB::transaction(function () use ($folder, $request, $result, $path, $disk) {
                $template = DocumentTemplate::lockForUpdate()->findOrFail($result['template']->id);
                abort_if($template->archived_at, 409, 'Este modelo foi arquivado. Selecione um modelo disponível e revise novamente.');
                $document = $folder->documents()->create([
                    'user_id' => $request->user()->id, 'name' => $request->string('name')->toString(),
                    'original_name' => (Str::slug($request->string('name')->toString()) ?: 'documento').'.docx',
                    'path' => $path, 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'size' => $disk->size($path),
                    'description' => 'Gerado do modelo #'.$result['template']->id.' — '.$result['template']->name.'. Revise no Word antes de utilizar.',
                ]);
                DB::table('document_generations')->insert([
                    'organization_id' => app(CurrentOrganization::class)->id(), 'document_template_id' => $result['template']->id,
                    'folder_document_id' => $document->id, 'values_snapshot' => json_encode($result['values'], JSON_UNESCAPED_UNICODE),
                    'template_sha256' => $result['template']->sha256, 'created_at' => now(), 'updated_at' => now(),
                ]);

                return $document->load('user:id,name');
            });
        } catch (\Throwable $error) {
            $disk->delete($path);
            throw $error;
        }

        return response()->json($document, 201);
    }
}
