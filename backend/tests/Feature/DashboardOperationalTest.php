<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOperationalTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            DatabaseSeeder::class
        );

        $this->organization =
            Organization::query()
            ->where(
                'slug',
                OrganizationSeeder::DEFAULT_SLUG,
            )
            ->firstOrFail();

        $this->user =
            User::query()
            ->where(
                'email',
                'super-admin@legalis.local',
            )
            ->firstOrFail();
    }

    public function test_dashboard_limita_listas_operacionais_a_cinco_itens(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com muitos itens',

                'process_number' =>
                '5001111-22.2026.8.21.0022',
            ]);

        foreach (
            range(1, 6) as $index
        ) {
            $folder
                ->events()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'hearing',

                    'title' =>
                    "Compromisso {$index}",

                    'starts_at' =>
                    now()->addDays(
                        $index
                    ),

                    'status' =>
                    'scheduled',
                ]);

            $folder
                ->deadlines()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    "Prazo {$index}",

                    'due_at' =>
                    now()->addDays(
                        $index
                    ),

                    'status' =>
                    'pending',
                ]);

            $folder
                ->tasks()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    "Tarefa {$index}",

                    'priority' =>
                    'medium',

                    'due_at' =>
                    now()->addDays(
                        $index
                    ),

                    'status' =>
                    'pending',
                ]);
        }

        $response =
            $this
            ->asTenant(
                $this->loginAsSuperAdmin(),
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                5,
                'operational.upcoming_events',
            )
            ->assertJsonCount(
                5,
                'operational.pending_deadlines',
            )
            ->assertJsonCount(
                5,
                'operational.pending_tasks',
            )
            ->assertJsonPath(
                'operational.upcoming_events.0.title',
                'Compromisso 1',
            )
            ->assertJsonPath(
                'operational.upcoming_events.4.title',
                'Compromisso 5',
            )
            ->assertJsonPath(
                'operational.pending_deadlines.0.title',
                'Prazo 1',
            )
            ->assertJsonPath(
                'operational.pending_deadlines.4.title',
                'Prazo 5',
            )
            ->assertJsonPath(
                'operational.pending_tasks.0.title',
                'Tarefa 1',
            )
            ->assertJsonPath(
                'operational.pending_tasks.4.title',
                'Tarefa 5',
            )
            ->assertJsonMissing([
                'title' =>
                'Compromisso 6',
            ])
            ->assertJsonMissing([
                'title' =>
                'Prazo 6',
            ])
            ->assertJsonMissing([
                'title' =>
                'Tarefa 6',
            ]);
    }

    public function test_dashboard_isola_listas_operacionais_por_organizacao(): void
    {
        $currentFolder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta da organização atual',

                'process_number' =>
                '5002222-33.2026.8.21.0022',
            ]);

        $currentFolder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'hearing',

                'title' =>
                'Audiência da organização atual',

                'starts_at' =>
                now()->addDay(),

                'status' =>
                'scheduled',
            ]);

        $currentFolder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Prazo da organização atual',

                'due_at' =>
                now()->addDays(2),

                'status' =>
                'pending',
            ]);

        $currentFolder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Tarefa da organização atual',

                'priority' =>
                'high',

                'due_at' =>
                now()->addHours(12),

                'status' =>
                'pending',
            ]);

        $otherOrganization =
            Organization::factory()
            ->create();

        $otherFolder =
            $otherOrganization
            ->folders()
            ->create([
                'name' =>
                'Pasta de outra organização',

                'process_number' =>
                '5009999-99.2026.8.21.9999',
            ]);

        $otherFolder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'meeting',

                'title' =>
                'Compromisso externo',

                'starts_at' =>
                now()->addHours(2),

                'status' =>
                'scheduled',
            ]);

        $otherFolder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Prazo externo',

                'due_at' =>
                now()->addHours(3),

                'status' =>
                'pending',
            ]);

        $otherFolder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Tarefa externa',

                'priority' =>
                'high',

                'due_at' =>
                now()->addHour(),

                'status' =>
                'pending',
            ]);

        $response =
            $this
            ->asTenant(
                $this->loginAsSuperAdmin(),
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'operational.upcoming_events.0.title',
                'Audiência da organização atual',
            )
            ->assertJsonPath(
                'operational.upcoming_events.0.folder.id',
                $currentFolder->id,
            )
            ->assertJsonPath(
                'operational.pending_deadlines.0.title',
                'Prazo da organização atual',
            )
            ->assertJsonPath(
                'operational.pending_deadlines.0.folder.id',
                $currentFolder->id,
            )
            ->assertJsonPath(
                'operational.pending_tasks.0.title',
                'Tarefa da organização atual',
            )
            ->assertJsonPath(
                'operational.pending_tasks.0.folder.id',
                $currentFolder->id,
            )
            ->assertJsonMissing([
                'title' =>
                'Compromisso externo',
            ])
            ->assertJsonMissing([
                'title' =>
                'Prazo externo',
            ])
            ->assertJsonMissing([
                'title' =>
                'Tarefa externa',
            ])
            ->assertJsonMissing([
                'name' =>
                'Pasta de outra organização',
            ]);
    }

    private function loginAsSuperAdmin(): string
    {
        return auth('api')->login(
            $this->user
        );
    }

    private function asTenant(
        string $token,
        Organization $organization,
    ): static {
        return $this
            ->withToken(
                $token
            )
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            );
    }
}
