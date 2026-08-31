<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\IntegrationSyncRun;
use App\Models\LegalPublication;
use App\Models\MonitoredBarRegistration;
use App\Models\Organization;
use App\Services\Publications\ImportDjenPublications;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportDjenPublicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_publicacao_e_vincula_pasta_pelo_numero_cnj(): void
    {
        $organization =
            Organization::factory()
                ->create();

        $registration =
            $this->createRegistration(
                $organization
            );

        $folder =
            Folder::factory()
                ->for($organization)
                ->create([
                    'process_number' => '5000000-00.2026.8.21.0001',
                ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    $this->publicationPayload(),
                ],
                'count' => 1,
            ]),
        ]);

        $syncRun =
            $this->service()->handle(
                $registration,
                CarbonImmutable::parse(
                    '2026-08-25'
                ),
                CarbonImmutable::parse(
                    '2026-08-28'
                ),
            );

        $publication =
            LegalPublication::query()
                ->firstOrFail();

        $this->assertSame(
            $organization->id,
            $publication->organization_id,
        );

        $this->assertSame(
            $folder->id,
            $publication->folder_id,
        );

        $this->assertSame(
            '987654',
            $publication->external_id,
        );

        $this->assertSame(
            '50000000020268210001',
            $publication->normalized_process_number,
        );

        $this->assertSame(
            LegalPublication::REVIEW_PENDING,
            $publication->review_status,
        );

        $this->assertTrue(
            $publication
                ->barRegistrations()
                ->whereKey(
                    $registration->id
                )
                ->exists()
        );

        $this->assertSame(
            IntegrationSyncRun::STATUS_SUCCEEDED,
            $syncRun->status,
        );

        $this->assertSame(
            1,
            $syncRun->items_seen,
        );

        $this->assertSame(
            1,
            $syncRun->items_imported,
        );

        $this->assertSame(
            1,
            $syncRun->items_linked,
        );

        Http::assertSent(
            fn (Request $request): bool => $request['numeroOab']
                    === '93556'
                && $request['ufOab']
                    === 'RS'
                && $request['dataDisponibilizacaoInicio']
                    === '2026-08-25'
                && $request['dataDisponibilizacaoFim']
                    === '2026-08-28'
        );
    }

    public function test_reprocessamento_nao_duplica_publicacao(): void
    {
        $organization =
            Organization::factory()
                ->create();

        $registration =
            $this->createRegistration(
                $organization
            );

        Http::fake([
            '*' => Http::response([
                'items' => [
                    $this->publicationPayload(),
                ],
                'count' => 1,
            ]),
        ]);

        $service =
            $this->service();

        $periodStart =
            CarbonImmutable::parse(
                '2026-08-25'
            );

        $periodEnd =
            CarbonImmutable::parse(
                '2026-08-28'
            );

        $service->handle(
            $registration,
            $periodStart,
            $periodEnd,
        );

        $secondRun =
            $service->handle(
                $registration->refresh(),
                $periodStart,
                $periodEnd,
            );

        $this->assertDatabaseCount(
            'legal_publications',
            1,
        );

        $this->assertDatabaseCount(
            'legal_publication_bar_registration',
            1,
        );

        $this->assertSame(
            0,
            $secondRun->items_imported,
        );
    }

    public function test_mesma_publicacao_pode_atingir_duas_oabs_sem_duplicar(): void
    {
        $organization =
            Organization::factory()
                ->create();

        $firstRegistration =
            $this->createRegistration(
                $organization
            );

        $secondRegistration =
            $organization
                ->monitoredBarRegistrations()
                ->create([
                    'lawyer_name' => 'Segundo Advogado',

                    'bar_number' => '12345',

                    'state' => 'RS',
                ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    $this->publicationPayload(),
                ],
                'count' => 1,
            ]),
        ]);

        $service =
            $this->service();

        $periodStart =
            CarbonImmutable::parse(
                '2026-08-25'
            );

        $periodEnd =
            CarbonImmutable::parse(
                '2026-08-28'
            );

        $service->handle(
            $firstRegistration,
            $periodStart,
            $periodEnd,
        );

        $service->handle(
            $secondRegistration,
            $periodStart,
            $periodEnd,
        );

        $this->assertDatabaseCount(
            'legal_publications',
            1,
        );

        $this->assertDatabaseCount(
            'legal_publication_bar_registration',
            2,
        );
    }

    public function test_publicacao_sem_pasta_permanece_na_caixa_de_nao_vinculadas(): void
    {
        $organization =
            Organization::factory()
                ->create();

        $registration =
            $this->createRegistration(
                $organization
            );

        Http::fake([
            '*' => Http::response([
                'items' => [
                    $this->publicationPayload(),
                ],
                'count' => 1,
            ]),
        ]);

        $this->service()->handle(
            $registration,
            CarbonImmutable::parse(
                '2026-08-25'
            ),
            CarbonImmutable::parse(
                '2026-08-28'
            ),
        );

        $this->assertDatabaseHas(
            'legal_publications',
            [
                'organization_id' => $organization->id,

                'external_id' => '987654',

                'folder_id' => null,
            ],
        );
    }

    public function test_falha_da_api_fica_registrada_na_auditoria(): void
    {
        $organization =
            Organization::factory()
                ->create();

        $registration =
            $this->createRegistration(
                $organization
            );

        Http::fake([
            '*' => Http::response(
                [
                    'message' => 'Serviço indisponível',
                ],
                503,
            ),
        ]);

        $exceptionThrown =
            false;

        try {
            $this->service()->handle(
                $registration,
                CarbonImmutable::parse(
                    '2026-08-25'
                ),
                CarbonImmutable::parse(
                    '2026-08-28'
                ),
            );

        } catch (\Throwable) {
            $exceptionThrown =
                true;
        }

        $this->assertTrue(
            $exceptionThrown,
            'A falha HTTP deve ser propagada para permitir a retentativa da fila.',
        );

        $this->assertDatabaseHas(
            'integration_sync_runs',
            [
                'organization_id' => $organization->id,

                'status' => IntegrationSyncRun::STATUS_FAILED,
            ],
        );

        $this->assertNull(
            $registration
                ->refresh()
                ->last_synced_at
        );
    }

    private function createRegistration(
        Organization $organization,
    ): MonitoredBarRegistration {
        return $organization
            ->monitoredBarRegistrations()
            ->create([
                'lawyer_name' => 'Advogada Monitorada',

                'bar_number' => '93556',

                'state' => 'RS',
            ]);
    }

    private function publicationPayload(): array
    {
        return [
            'id' => 987654,

            'hash' => 'hash-da-comunicacao',

            'data_disponibilizacao' => '2026-08-27',

            'siglaTribunal' => 'TJRS',

            'tipoComunicacao' => 'Intimação',

            'tipoDocumento' => 'Despacho',

            'nomeOrgao' => '2ª Vara Cível da Comarca de Pelotas',

            'numero_processo' => '50000000020268210001',

            'meio' => 'D',

            'texto' => 'Fica a parte intimada do despacho proferido.',

            'destinatarios' => [
                [
                    'nome' => 'Parte autora',
                ],
            ],

            'destinatarioadvogados' => [
                [
                    'nome' => 'Advogada Monitorada',

                    'numero_oab' => '93556',

                    'uf_oab' => 'RS',
                ],
            ],
        ];
    }

    private function service(): ImportDjenPublications
    {
        return app(
            ImportDjenPublications::class
        );
    }
}
