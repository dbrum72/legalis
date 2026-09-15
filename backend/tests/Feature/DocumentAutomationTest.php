<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class DocumentAutomationTest extends TestCase
{
    use RefreshDatabase;

    private Folder $folder;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        $this->organization = Organization::where('slug', OrganizationSeeder::DEFAULT_SLUG)->firstOrFail();
        $this->folder = $this->organization->folders()->create(['name' => 'Inicial sem processo', 'process_number' => null]);
        $user = User::where('email', 'super-admin@legalis.local')->firstOrFail();
        $this->withToken(auth('api')->login($user))->withHeader('X-Tenant', $this->organization->slug);
    }

    private function upload(string $text = '{{ pasta.nome }} {{ campo.fatos }}', ?int $replace = null)
    {
        $path = tempnam(sys_get_temp_dir(), 'template');
        try {
            $zip = new ZipArchive;
            $zip->open($path, ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', '<Types/>');
            $zip->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.$text.'</w:t></w:r></w:p></w:body></w:document>');
            $zip->close();

            return $this->postJson('/api/document-templates'.($replace ? '/'.$replace.'/replace' : ''), ['name' => 'Inicial', 'file' => new UploadedFile($path, 'inicial.docx', null, null, true)]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function url(): string
    {
        return '/api/folders/'.$this->folder->id.'/document-generation';
    }

    public function test_catalog_is_available_without_a_folder_and_import_requires_management(): void
    {
        $id = $this->upload()->assertCreated()->json('id');
        $user = User::factory()->create();
        $this->organization->users()->attach($user->id, ['status' => 'active']);
        setPermissionsTeamId($this->organization->id);
        $user->givePermissionTo('documents.generate');
        $this->withToken(auth('api')->login($user));
        $response = $this->getJson('/api/document-templates/fields')->assertOk();
        $this->assertSame('Número do processo', $response->json()['processo.numero']);
        $this->assertArrayNotHasKey('parties', $response->json());
        $this->getJson('/api/document-templates')->assertOk();
        $this->postJson('/api/document-templates', [])->assertForbidden();
        $this->deleteJson('/api/document-templates/'.$id)->assertForbidden();
        $this->postJson('/api/document-templates/'.$id.'/replace', [])->assertForbidden();
        $this->getJson($this->url().'/context')->assertForbidden();
    }

    public function test_import_preview_complete_generate_and_download_with_snapshot(): void
    {
        $template = $this->upload()->assertCreated()->json();
        $this->assertArrayNotHasKey('path', $template);
        $data = ['template_id' => $template['id'], 'values' => []];
        $this->postJson($this->url().'/preview', $data)->assertOk()->assertJsonPath('missing.0', 'campo.fatos');
        $data['values'] = ['campo.fatos' => "João & Maria\nFatos revisados"];
        $preview = $this->postJson($this->url().'/preview', $data)->assertOk()->assertJsonPath('missing', [])->json();
        $document = $this->postJson($this->url(), [...$data, 'name' => 'Inicial revisada', 'signature' => $preview['signature']])->assertCreated()->json();
        $this->assertDatabaseHas('document_generations', ['folder_document_id' => $document['id'], 'document_template_id' => $template['id']]);
        $this->get('/api/folders/'.$this->folder->id.'/documents/'.$document['id'].'/download')->assertOk();
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path($document['path']));
        $this->assertStringContainsString('João &amp; Maria', $zip->getFromName('word/document.xml'));
        $zip->close();
    }

    public function test_missing_fields_and_stale_preview_cannot_generate(): void
    {
        $id = $this->upload()->assertCreated()->json('id');
        $data = ['template_id' => $id, 'values' => [], 'name' => 'Inicial'];
        $preview = $this->postJson($this->url().'/preview', $data)->assertOk()->json();
        $this->postJson($this->url(), [...$data, 'signature' => $preview['signature']])->assertUnprocessable();
        $data['values'] = ['campo.fatos' => 'Fatos'];
        $preview = $this->postJson($this->url().'/preview', $data)->assertOk()->json();
        $this->folder->update(['name' => 'Alterada']);
        $this->postJson($this->url(), [...$data, 'signature' => $preview['signature']])->assertConflict();
        $this->assertDatabaseCount('document_generations', 0);
    }

    public function test_unknown_fields_and_foreign_template_are_rejected(): void
    {
        $this->upload('{{ cliente.inventado }}')->assertUnprocessable();
        $id = $this->upload()->assertCreated()->json('id');
        $other = Organization::create(['name' => 'Outro', 'slug' => 'other-documents', 'status' => 'active']);
        \DB::table('document_templates')->where('id', $id)->update(['organization_id' => $other->id]);
        $this->getJson('/api/document-templates')->assertOk()->assertExactJson([]);
        $this->get('/api/document-templates/'.$id.'/download')->assertNotFound();
        $this->deleteJson('/api/document-templates/'.$id)->assertNotFound();
        $this->upload('{{ pasta.nome }}', $id)->assertNotFound();
        $this->postJson($this->url().'/preview', ['template_id' => $id, 'values' => []])->assertNotFound();
    }

    public function test_client_marker_requires_explicit_party_and_permission_is_enforced(): void
    {
        $id = $this->upload('{{ cliente.nome }}')->assertCreated()->json('id');
        $this->postJson($this->url().'/preview', ['template_id' => $id, 'values' => []])->assertUnprocessable()->assertJsonValidationErrors('party_id');
        $user = User::factory()->create();
        $this->organization->users()->attach($user->id, ['status' => 'active']);
        $this->withToken(auth('api')->login($user));
        $this->getJson('/api/document-templates')->assertForbidden();
    }

    public function test_rejects_non_text_and_control_characters_in_dotted_field_names(): void
    {
        $id = $this->upload()->assertCreated()->json('id');
        foreach ([['nested'], "Texto\x01", str_repeat('a', 20001)] as $value) {
            $this->postJson($this->url().'/preview', ['template_id' => $id, 'values' => ['campo.fatos' => $value]])->assertUnprocessable();
        }
    }

    public function test_unused_template_can_be_deleted_with_its_file(): void
    {
        $id = $this->upload()->assertCreated()->json('id');
        $path = \DB::table('document_templates')->where('id', $id)->value('path');
        $this->deleteJson('/api/document-templates/'.$id)->assertOk()->assertJsonPath('archived', false);
        $this->assertDatabaseMissing('document_templates', ['id' => $id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_conditional_process_section_is_optional_and_rechecked_on_generation(): void
    {
        $id = $this->upload('{{ pasta.nome }}{{#se processo.numero}} em Ação {{ processo.numero }}{{/se}}')->assertCreated()->json('id');
        $data = ['template_id' => $id, 'values' => []];
        $preview = $this->postJson($this->url().'/preview', $data)->assertOk()->assertJsonPath('missing', [])->assertJsonPath('text', 'Inicial sem processo')->json();
        $this->postJson($this->url(), [...$data, 'name' => 'Sem processo', 'signature' => $preview['signature']])->assertCreated();
        $this->folder->update(['process_number' => '12345']);
        $this->postJson($this->url(), [...$data, 'name' => 'Prévia antiga', 'signature' => $preview['signature']])->assertConflict();
        $this->postJson($this->url().'/preview', $data)->assertOk()->assertJsonPath('text', 'Inicial sem processo em Ação 12345');
    }

    public function test_only_fields_in_visible_sections_are_required(): void
    {
        $id = $this->upload('{{#se campo.ativar}}Texto {{ campo.detalhe }}{{/se}} {{ campo.obrigatorio }}')->assertCreated()->json('id');
        $data = ['template_id' => $id, 'values' => ['campo.obrigatorio' => 'Presente']];
        $this->postJson($this->url().'/preview', $data)->assertOk()->assertJsonPath('missing', []);
        $data['values']['campo.ativar'] = 'Sim';
        $this->postJson($this->url().'/preview', $data)->assertOk()->assertJsonPath('missing', ['campo.detalhe']);
        $this->upload('{{#se desconhecido.campo}}Texto{{/se}}')->assertUnprocessable();
    }

    public function test_used_template_is_archived_and_existing_document_remains_identical(): void
    {
        $id = $this->upload('{{ pasta.nome }}')->assertCreated()->json('id');
        $data = ['template_id' => $id, 'values' => []];
        $preview = $this->postJson($this->url().'/preview', $data)->assertOk()->json();
        $payload = [...$data, 'name' => 'Documento', 'signature' => $preview['signature']];
        $document = $this->postJson($this->url(), $payload)->assertCreated()->json();
        $before = Storage::disk('local')->get($document['path']);
        $this->deleteJson('/api/document-templates/'.$id)->assertOk()->assertJsonPath('archived', true);
        $this->getJson('/api/document-templates')->assertExactJson([]);
        $this->getJson('/api/document-templates?include_archived=1')->assertJsonCount(1);
        $this->get('/api/document-templates/'.$id.'/download')->assertOk();
        $this->postJson($this->url(), $payload)->assertConflict();
        $this->postJson($this->url().'/preview', $data)->assertConflict();
        $this->get('/api/folders/'.$this->folder->id.'/documents/'.$document['id'].'/download')->assertOk();
        $this->assertSame($before, Storage::disk('local')->get($document['path']));
        $this->assertDatabaseHas('document_generations', ['folder_document_id' => $document['id'], 'document_template_id' => $id]);
    }

    public function test_replacement_validates_first_and_preserves_original(): void
    {
        $id = $this->upload()->assertCreated()->json('id');
        $path = \DB::table('document_templates')->where('id', $id)->value('path');
        $original = Storage::disk('local')->get($path);
        $this->upload('{{ desconhecido.campo }}', $id)->assertUnprocessable();
        $this->assertDatabaseHas('document_templates', ['id' => $id, 'archived_at' => null]);
        $newId = $this->upload('{{ pasta.nome }}', $id)->assertCreated()->json('id');
        $this->assertNotEquals($id, $newId);
        $this->getJson('/api/document-templates')->assertJsonCount(1)->assertJsonPath('0.id', $newId);
        $this->assertNotNull(\DB::table('document_templates')->where('id', $id)->value('archived_at'));
        $this->assertSame($original, Storage::disk('local')->get($path));
        $this->upload('{{ pasta.nome }}', $id)->assertConflict();
        $this->assertDatabaseCount('document_templates', 2);
        $this->assertCount(2, Storage::disk('local')->allFiles());
    }
}
