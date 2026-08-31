<?php

namespace Tests\Feature;

use App\Models\IntegrationSyncRun;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDataJudIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_exibe_integracao_datajud_nao_vista_e_permite_marca_la_como_vista(): void
    {
        $this->seed(DatabaseSeeder::class);

        $organization = Organization::where('slug', OrganizationSeeder::DEFAULT_SLUG)->firstOrFail();
        $user = User::where('email', 'super-admin@legalis.local')->firstOrFail();
        $folder = $organization->folders()->create([
            'name' => 'Processo acompanhado',
            'process_number' => '5000000-00.2026.8.21.0001',
        ]);
        $run = IntegrationSyncRun::withoutGlobalScopes()->create([
            'organization_id' => $organization->id,
            'folder_id' => $folder->id,
            'provider' => 'datajud',
            'status' => IntegrationSyncRun::STATUS_SUCCEEDED,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'items_seen' => 4,
            'items_imported' => 2,
        ]);
        $headers = [
            'Authorization' => 'Bearer '.auth('api')->login($user),
            'X-Tenant' => $organization->slug,
        ];

        $this->withHeaders($headers)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('unseen_datajud_integrations.0.id', $run->id)
            ->assertJsonPath('unseen_datajud_integrations.0.items_imported', 2)
            ->assertJsonPath('unseen_datajud_integrations.0.folder.id', $folder->id);

        $this->withHeaders($headers)
            ->postJson("/api/dashboard/datajud-integrations/{$run->id}/seen")
            ->assertOk();

        $this->assertDatabaseHas('integration_sync_run_views', [
            'integration_sync_run_id' => $run->id,
            'user_id' => $user->id,
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonCount(0, 'unseen_datajud_integrations');
    }
}
