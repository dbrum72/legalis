<?php

namespace Tests\Feature;

use App\Integrations\DataJud\Contracts\DataJudClient;
use App\Jobs\SyncFolderDataJud;
use App\Models\Folder;
use App\Models\IntegrationSyncRun;
use App\Models\Organization;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DataJudScheduledSyncTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->organization = Organization::where('slug', OrganizationSeeder::DEFAULT_SLUG)->firstOrFail();
        $this->folder = $this->organization->folders()->create([
            'name' => 'Pasta monitorada',
            'process_number' => '5000000-00.2026.8.21.0001',
            'datajud_monitoring_enabled' => true,
        ]);
    }

    public function test_comando_enfileira_apenas_pastas_monitoradas_e_devidas(): void
    {
        Queue::fake();

        $this->organization->folders()->create([
            'name' => 'Sem monitoramento',
            'process_number' => '5000001-00.2026.8.21.0001',
            'datajud_monitoring_enabled' => false,
        ]);

        $this->artisan('datajud:sync-folders')
            ->expectsOutput('1 pasta(s) processada(s).')
            ->assertSuccessful();

        Queue::assertPushed(SyncFolderDataJud::class, 1);
        Queue::assertPushed(
            SyncFolderDataJud::class,
            fn (SyncFolderDataJud $job) => $job->folderId === $this->folder->id,
        );
    }

    public function test_job_registra_sucesso_e_proxima_execucao(): void
    {
        $client = Mockery::mock(DataJudClient::class);
        $client->shouldReceive('findProcess')->once()->andReturn([
            'tribunal' => 'TJRS',
            'movimentos' => [[
                'codigo' => 26,
                'nome' => 'Distribuição',
                'dataHora' => '2026-08-30T14:00:00.000Z',
            ]],
        ]);
        $this->app->instance(DataJudClient::class, $client);

        $job = new SyncFolderDataJud($this->folder->id, '2026-08-31');
        $job->handle($this->app->make(\App\Services\Folders\SyncFolderWithDataJud::class));

        $this->folder->refresh();

        $this->assertNotNull($this->folder->datajud_last_attempt_at);
        $this->assertNotNull($this->folder->datajud_last_success_at);
        $this->assertNotNull($this->folder->datajud_next_sync_at);
        $this->assertNull($this->folder->datajud_sync_error);
        $this->assertDatabaseHas('integration_sync_runs', [
            'folder_id' => $this->folder->id,
            'provider' => 'datajud',
            'status' => IntegrationSyncRun::STATUS_SUCCEEDED,
            'items_seen' => 1,
            'items_imported' => 1,
        ]);
    }

    public function test_job_registra_falha_e_relanca_excecao(): void
    {
        $client = Mockery::mock(DataJudClient::class);
        $client->shouldReceive('findProcess')->once()->andThrow(new RuntimeException('DataJud indisponível'));
        $this->app->instance(DataJudClient::class, $client);

        $this->expectException(RuntimeException::class);

        try {
            (new SyncFolderDataJud($this->folder->id, '2026-08-31'))
                ->handle($this->app->make(\App\Services\Folders\SyncFolderWithDataJud::class));
        } finally {
            $this->assertDatabaseHas('integration_sync_runs', [
                'folder_id' => $this->folder->id,
                'provider' => 'datajud',
                'status' => IntegrationSyncRun::STATUS_FAILED,
                'error_message' => 'DataJud indisponível',
            ]);
        }
    }
}
