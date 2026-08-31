<?php

namespace Tests\Feature;

use App\Integrations\DataJud\Contracts\DataJudClient;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use App\Services\Folders\SyncFolderWithDataJud;

class DataJudSyncTest extends TestCase
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
            'name' => 'Processo DataJud',
            'process_number' => '5000000-00.2026.8.21.0001',
        ]);
    }

    public function test_sincroniza_metadados_e_movimentacoes_sem_duplicar(): void
    {
        $client = Mockery::mock(DataJudClient::class);
        $client->shouldReceive('findProcess')->twice()->with('tjrs', '50000000020268210001')->andReturn([
            'numeroProcesso' => '50000000020268210001',
            'tribunal' => 'TJRS',
            'grau' => 'G1',
            'classe' => ['codigo' => 1116, 'nome' => 'Execução Fiscal'],
            'orgaoJulgador' => ['codigo' => 1, 'nome' => '1ª Vara'],
            'movimentos' => [[
                'codigo' => 26,
                'nome' => 'Distribuição',
                'dataHora' => '2026-08-30T14:00:00.000Z',
                'orgaoJulgador' => ['codigo' => '1', 'nome' => '1ª Vara'],
                'complementosTabelados' => [[
                    'codigo' => 3,
                    'descricao' => 'tipo_de_distribuicao',
                    'valor' => 1,
                    'nome' => 'Sorteio',
                ]],
            ]],
        ]);
        $this->app->instance(DataJudClient::class, $client);

        $service = $this->app->make(SyncFolderWithDataJud::class);

        $this->assertSame(1, $service->execute($this->folder)['movements_imported']);
        $this->assertSame(0, $service->execute($this->folder)['movements_imported']);

        $this->assertDatabaseCount('folder_movements', 1);
        $this->assertDatabaseHas('folder_movements', [
            'folder_id' => $this->folder->id,
            'source' => 'datajud',
            'source_code' => '26',
            'description' => "Órgão: 1ª Vara\nTipo De Distribuicao: Sorteio",
        ]);
        $this->assertDatabaseHas('folders', [
            'id' => $this->folder->id,
            'datajud_alias' => 'tjrs',
        ]);
    }

    public function test_sincronizacao_manual_executa_imediatamente(): void
    {
        $client = Mockery::mock(DataJudClient::class);
        $client->shouldReceive('findProcess')->once()->andReturn([
            'numeroProcesso' => '50000000020268210001',
            'tribunal' => 'TJRS',
            'grau' => 'G1',
            'movimentos' => [[
                'codigo' => 26,
                'nome' => 'Distribuição',
                'dataHora' => '2026-08-30T14:00:00.000Z',
            ]],
        ]);
        $this->app->instance(DataJudClient::class, $client);

        $this->asTenant($this->loginAsSuperAdmin())
            ->postJson($this->url())
            ->assertOk()
            ->assertJsonPath('queued', false);

        $this->assertDatabaseHas('folder_movements', [
            'folder_id' => $this->folder->id,
            'source' => 'datajud',
            'source_code' => '26',
        ]);
        $this->assertDatabaseHas('integration_sync_runs', [
            'folder_id' => $this->folder->id,
            'provider' => 'datajud',
            'status' => 'succeeded',
        ]);
    }

    public function test_nao_exclui_movimentacao_importada(): void
    {
        $movement = $this->folder->movements()->create([
            'source' => 'datajud',
            'external_id' => hash('sha256', 'movement'),
            'source_code' => '26',
            'occurred_at' => now(),
            'title' => 'Distribuição',
        ]);

        $this->asTenant($this->loginAsSuperAdmin())
            ->deleteJson("/api/folders/{$this->folder->id}/movements/{$movement->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('folder_movements', ['id' => $movement->id]);
    }

    public function test_rejeita_pasta_sem_numero_cnj_valido(): void
    {
        $this->folder->update(['process_number' => '123']);

        $this->asTenant($this->loginAsSuperAdmin())
            ->postJson($this->url())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('process_number');
    }

    public function test_informa_quando_processo_nao_e_encontrado(): void
    {
        $client = Mockery::mock(DataJudClient::class);
        $client->shouldReceive('findProcess')->once()->andReturnNull();
        $this->app->instance(DataJudClient::class, $client);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->app->make(SyncFolderWithDataJud::class)->execute($this->folder);
    }

    private function url(): string
    {
        return "/api/folders/{$this->folder->id}/datajud/sync";
    }

    private function loginAsSuperAdmin(): string
    {
        return auth('api')->login(User::where('email', 'super-admin@legalis.local')->firstOrFail());
    }

    private function asTenant(string $token): static
    {
        return $this->withToken($token)->withHeader('X-Tenant', $this->organization->slug);
    }
}
