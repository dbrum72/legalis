<?php

namespace App\Services\Folders;

use App\Integrations\DataJud\Contracts\DataJudClient;
use App\Integrations\DataJud\DataJudEndpointResolver;
use App\Models\Folder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SyncFolderWithDataJud
{
    public function __construct(
        private readonly DataJudClient $client,
        private readonly DataJudEndpointResolver $resolver,
    ) {}

    public function execute(Folder $folder): array
    {
        try {
            $number = $this->resolver->normalizeProcessNumber(
                (string) $folder->process_number,
            );
            $alias = $this->resolver->resolve($number);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'process_number' => [$exception->getMessage()],
            ]);
        }

        $process = $this->client->findProcess($alias, $number);

        if ($process === null) {
            throw ValidationException::withMessages([
                'process_number' => [
                    'O processo não foi encontrado na API Pública do DataJud.',
                ],
            ]);
        }

        $imported = DB::transaction(
            fn (): int => $this->persist($folder, $process, $alias),
        );

        return [
            'message' => 'Dados do processo atualizados pelo DataJud.',
            'movements_seen' => count($process['movimentos'] ?? []),
            'movements_imported' => $imported,
            'datajud_synced_at' => $folder->fresh()->datajud_synced_at,
        ];
    }

    private function persist(
        Folder $folder,
        array $process,
        string $alias,
    ): int {
        $folder->forceFill([
            'datajud_alias' => $alias,
            'datajud_metadata' => [
                'tribunal' => $process['tribunal'] ?? null,
                'grau' => $process['grau'] ?? null,
                'nivel_sigilo' => $process['nivelSigilo'] ?? null,
                'data_ajuizamento' => $process['dataAjuizamento'] ?? null,
                'classe' => $process['classe'] ?? null,
                'assuntos' => $process['assuntos'] ?? [],
                'orgao_julgador' => $process['orgaoJulgador'] ?? null,
                'sistema' => $process['sistema'] ?? null,
                'formato' => $process['formato'] ?? null,
                'ultima_atualizacao' => $process['dataHoraUltimaAtualizacao'] ?? null,
            ],
            'datajud_synced_at' => now(),
        ])->save();

        $imported = 0;

        foreach (($process['movimentos'] ?? []) as $movement) {
            if (! is_array($movement) || empty($movement['dataHora'])) {
                continue;
            }

            $externalId = hash('sha256', json_encode([
                $movement['codigo'] ?? null,
                $movement['nome'] ?? null,
                $movement['dataHora'],
                $movement['orgaoJulgador'] ?? null,
                $movement['complementosTabelados'] ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $model = $folder->movements()->firstOrNew(
                [
                    'source' => 'datajud',
                    'external_id' => $externalId,
                ],
            );

            $isNew = ! $model->exists;

            $model->fill([
                'user_id' => null,
                'occurred_at' => CarbonImmutable::parse($movement['dataHora']),
                'title' => (string) ($movement['nome'] ?? 'Movimentação processual'),
                'description' => $this->description($movement),
                'source_code' => isset($movement['codigo'])
                    ? (string) $movement['codigo']
                    : null,
                'source_metadata' => [
                    'orgao_julgador' => $movement['orgaoJulgador'] ?? null,
                    'complementos' => $movement['complementosTabelados'] ?? [],
                ],
            ]);

            $model->save();

            if ($isNew) {
                $imported++;
            }
        }

        return $imported;
    }

    private function description(array $movement): ?string
    {
        $parts = [];
        $organ = $movement['orgaoJulgador']['nome']
            ?? $movement['orgaoJulgador']['nomeOrgao']
            ?? null;

        if (is_string($organ) && $organ !== '') {
            $parts[] = 'Órgão: '.$organ;
        }

        foreach (($movement['complementosTabelados'] ?? []) as $complement) {
            if (! is_array($complement)) {
                continue;
            }

            $label = $complement['descricao'] ?? $complement['nome'] ?? null;
            $value = $complement['nome'] ?? $complement['valor'] ?? null;

            if ($label !== null && $value !== null) {
                $parts[] = $this->humanize((string) $label).': '.$value;
            }
        }

        return $parts === [] ? null : implode("\n", array_unique($parts));
    }

    private function humanize(string $value): string
    {
        return mb_convert_case(
            str_replace('_', ' ', $value),
            MB_CASE_TITLE,
            'UTF-8',
        );
    }
}
