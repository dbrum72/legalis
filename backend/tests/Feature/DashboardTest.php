<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

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
    }

    public function test_dashboard_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertUnauthorized();
    }

    public function test_dashboard_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertBadRequest();
    }

    public function test_dashboard_retorna_resumo_da_organizacao_atual(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this->organization
            ->clients()
            ->createMany([
                [
                    'name' =>
                    'Cliente A',

                    'document' =>
                    '11111111111',
                ],
                [
                    'name' =>
                    'Cliente B',

                    'document' =>
                    '22222222222',
                ],
            ]);

        $this->organization
            ->folders()
            ->createMany([
                [
                    'name' =>
                    'Pasta A',

                    'process_number' =>
                    null,
                ],
                [
                    'name' =>
                    'Pasta B',

                    'process_number' =>
                    '5000000-00.2026.8.21.0001',
                ],
                [
                    'name' =>
                    'Pasta C',

                    'process_number' =>
                    null,
                ],
            ]);

        $activeMember =
            User::factory()
            ->create();

        $inactiveMember =
            User::factory()
            ->create();

        $this->attachUser(
            $activeMember,
            $this->organization,
            'active',
        );

        $this->attachUser(
            $inactiveMember,
            $this->organization,
            'inactive',
        );

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'summary.clients',
                2,
            )
            ->assertJsonPath(
                'summary.folders',
                3,
            );

        $this->assertIsInt(
            $response->json(
                'summary.active_members'
            )
        );
    }

    public function test_dashboard_conta_apenas_membros_ativos(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $before =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $before
            ->assertOk();

        $initialCount =
            $before->json(
                'summary.active_members'
            );

        $activeMember =
            User::factory()
            ->create();

        $inactiveMember =
            User::factory()
            ->create();

        $this->attachUser(
            $activeMember,
            $this->organization,
            'active',
        );

        $this->attachUser(
            $inactiveMember,
            $this->organization,
            'inactive',
        );

        $after =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $after
            ->assertOk()
            ->assertJsonPath(
                'summary.active_members',
                $initialCount + 1,
            );
    }

    public function test_dashboard_isola_dados_por_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $otherOrganization =
            Organization::factory()
            ->create();

        $this->organization
            ->clients()
            ->create([
                'name' =>
                'Cliente da organização atual',

                'document' =>
                '33333333333',
            ]);

        $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta da organização atual',

                'process_number' =>
                null,
            ]);

        $otherOrganization
            ->clients()
            ->createMany([
                [
                    'name' =>
                    'Cliente externo A',

                    'document' =>
                    '44444444444',
                ],
                [
                    'name' =>
                    'Cliente externo B',

                    'document' =>
                    '55555555555',
                ],
            ]);

        $otherOrganization
            ->folders()
            ->createMany([
                [
                    'name' =>
                    'Pasta externa A',

                    'process_number' =>
                    null,
                ],
                [
                    'name' =>
                    'Pasta externa B',

                    'process_number' =>
                    null,
                ],
            ]);

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.clients',
                1,
            )
            ->assertJsonPath(
                'summary.folders',
                1,
            )
            ->assertJsonMissing([
                'name' =>
                'Pasta externa A',
            ])
            ->assertJsonMissing([
                'name' =>
                'Pasta externa B',
            ]);
    }

    public function test_dashboard_retorna_as_cinco_pastas_mais_recentes(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        foreach (
            range(1, 6) as $index
        ) {
            $folder =
                $this->organization
                ->folders()
                ->create([
                    'name' =>
                    "Pasta {$index}",

                    'process_number' =>
                    $index % 2 === 0
                        ? "500000{$index}-00.2026.8.21.0001"
                        : null,
                ]);

            $folder->forceFill([
                'created_at' =>
                now()->subDays(
                    6 - $index
                ),
            ])->saveQuietly();
        }

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                5,
                'recent_folders',
            )
            ->assertJsonPath(
                'recent_folders.0.name',
                'Pasta 6',
            )
            ->assertJsonPath(
                'recent_folders.1.name',
                'Pasta 5',
            )
            ->assertJsonPath(
                'recent_folders.4.name',
                'Pasta 2',
            )
            ->assertJsonMissing([
                'name' =>
                'Pasta 1',
            ]);
    }

    public function test_dashboard_retorna_contrato_minimo_das_pastas_recentes(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Ação indenizatória',

                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertOk()
            ->assertJsonPath(
                'recent_folders.0.id',
                $folder->id,
            )
            ->assertJsonPath(
                'recent_folders.0.name',
                'Ação indenizatória',
            )
            ->assertJsonPath(
                'recent_folders.0.process_number',
                '5000000-00.2026.8.21.0001',
            )
            ->assertJsonStructure([
                'summary' => [
                    'clients',
                    'folders',
                    'active_members',
                    'pending_tasks',
                    'pending_deadlines',
                    'upcoming_events',
                ],

                'recent_folders' => [
                    '*' => [
                        'id',
                        'name',
                        'process_number',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_dashboard_retorna_resumo_operacional_da_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $user =
            User::query()
            ->where(
                'email',
                'super-admin@legalis.local',
            )
            ->firstOrFail();

        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta operacional',

                'process_number' =>
                '5001234-56.2026.8.21.0001',
            ]);

        $folder
            ->tasks()
            ->createMany([
                [
                    'user_id' =>
                    $user->id,

                    'title' =>
                    'Preparar contestação',

                    'priority' =>
                    'high',

                    'status' =>
                    'pending',
                ],
                [
                    'user_id' =>
                    $user->id,

                    'title' =>
                    'Revisar documentos',

                    'priority' =>
                    'medium',

                    'status' =>
                    'pending',
                ],
                [
                    'user_id' =>
                    $user->id,

                    'title' =>
                    'Tarefa já concluída',

                    'priority' =>
                    'low',

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now(),
                ],
            ]);

        $folder
            ->deadlines()
            ->createMany([
                [
                    'user_id' =>
                    $user->id,

                    'title' =>
                    'Apresentar contestação',

                    'due_at' =>
                    now()->addDays(5),

                    'status' =>
                    'pending',
                ],
                [
                    'user_id' =>
                    $user->id,

                    'title' =>
                    'Interpor recurso',

                    'due_at' =>
                    now()->addDays(10),

                    'status' =>
                    'pending',
                ],
                [
                    'user_id' =>
                    $user->id,

                    'title' =>
                    'Prazo já concluído',

                    'due_at' =>
                    now()->subDay(),

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now(),
                ],
            ]);

        $folder
            ->events()
            ->createMany([
                [
                    'user_id' =>
                    $user->id,

                    'type' =>
                    'hearing',

                    'title' =>
                    'Audiência de instrução',

                    'starts_at' =>
                    now()->addDays(3),

                    'status' =>
                    'scheduled',
                ],
                [
                    'user_id' =>
                    $user->id,

                    'type' =>
                    'meeting',

                    'title' =>
                    'Reunião com cliente',

                    'starts_at' =>
                    now()->addDays(7),

                    'status' =>
                    'scheduled',
                ],
                [
                    'user_id' =>
                    $user->id,

                    'type' =>
                    'meeting',

                    'title' =>
                    'Compromisso passado',

                    'starts_at' =>
                    now()->subDay(),

                    'status' =>
                    'scheduled',
                ],
                [
                    'user_id' =>
                    $user->id,

                    'type' =>
                    'hearing',

                    'title' =>
                    'Audiência concluída',

                    'starts_at' =>
                    now()->addDay(),

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now(),
                ],
            ]);

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.pending_tasks',
                2,
            )
            ->assertJsonPath(
                'summary.pending_deadlines',
                2,
            )
            ->assertJsonPath(
                'summary.upcoming_events',
                2,
            );
    }

    public function test_dashboard_retorna_listas_operacionais_da_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $user =
            User::query()
            ->where(
                'email',
                'super-admin@legalis.local',
            )
            ->firstOrFail();

        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Ação de cobrança',

                'process_number' =>
                '5009876-54.2026.8.21.0022',
            ]);

        /*
         * Agenda:
         * o compromisso mais próximo deve aparecer primeiro.
         */

        $nextEvent =
            $folder
            ->events()
            ->create([
                'user_id' =>
                $user->id,

                'type' =>
                'hearing',

                'title' =>
                'Audiência de conciliação',

                'starts_at' =>
                now()->addDays(2),

                'location' =>
                'Fórum de Pelotas',

                'status' =>
                'scheduled',
            ]);

        $folder
            ->events()
            ->create([
                'user_id' =>
                $user->id,

                'type' =>
                'meeting',

                'title' =>
                'Reunião posterior',

                'starts_at' =>
                now()->addDays(6),

                'status' =>
                'scheduled',
            ]);

        /*
         * Este evento não pode integrar
         * a coleção operacional.
         */

        $folder
            ->events()
            ->create([
                'user_id' =>
                $user->id,

                'type' =>
                'meeting',

                'title' =>
                'Evento passado',

                'starts_at' =>
                now()->subDay(),

                'status' =>
                'scheduled',
            ]);

        /*
         * Prazos:
         * ordenados por due_at crescente.
         */

        $nextDeadline =
            $folder
            ->deadlines()
            ->create([
                'user_id' =>
                $user->id,

                'title' =>
                'Apresentar manifestação',

                'due_at' =>
                now()->addDay(),

                'status' =>
                'pending',
            ]);

        $folder
            ->deadlines()
            ->create([
                'user_id' =>
                $user->id,

                'title' =>
                'Interpor recurso',

                'due_at' =>
                now()->addDays(8),

                'status' =>
                'pending',
            ]);

        /*
         * O prazo concluído não deve aparecer
         * em operational.pending_deadlines.
         */

        $folder
            ->deadlines()
            ->create([
                'user_id' =>
                $user->id,

                'title' =>
                'Prazo concluído',

                'due_at' =>
                now()->addHours(6),

                'status' =>
                'completed',

                'completed_at' =>
                now(),
            ]);

        /*
         * Tarefas:
         * vencimentos mais próximos primeiro.
         */

        $nextTask =
            $folder
            ->tasks()
            ->create([
                'user_id' =>
                $user->id,

                'title' =>
                'Revisar documentos',

                'priority' =>
                'high',

                'due_at' =>
                now()->addHours(12),

                'status' =>
                'pending',
            ]);

        $folder
            ->tasks()
            ->create([
                'user_id' =>
                $user->id,

                'title' =>
                'Telefonar para cliente',

                'priority' =>
                'medium',

                'due_at' =>
                now()->addDays(4),

                'status' =>
                'pending',
            ]);

        /*
         * A concluída também não pode aparecer
         * em operational.pending_tasks.
         */

        $folder
            ->tasks()
            ->create([
                'user_id' =>
                $user->id,

                'title' =>
                'Tarefa concluída',

                'priority' =>
                'low',

                'due_at' =>
                now()->addHour(),

                'status' =>
                'completed',

                'completed_at' =>
                now(),
            ]);

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'operational.upcoming_events.0.id',
                $nextEvent->id,
            )
            ->assertJsonPath(
                'operational.upcoming_events.0.title',
                'Audiência de conciliação',
            )
            ->assertJsonPath(
                'operational.upcoming_events.0.folder.id',
                $folder->id,
            )
            ->assertJsonPath(
                'operational.upcoming_events.0.folder.name',
                'Ação de cobrança',
            )
            ->assertJsonPath(
                'operational.pending_deadlines.0.id',
                $nextDeadline->id,
            )
            ->assertJsonPath(
                'operational.pending_deadlines.0.title',
                'Apresentar manifestação',
            )
            ->assertJsonPath(
                'operational.pending_deadlines.0.folder.id',
                $folder->id,
            )
            ->assertJsonPath(
                'operational.pending_tasks.0.id',
                $nextTask->id,
            )
            ->assertJsonPath(
                'operational.pending_tasks.0.title',
                'Revisar documentos',
            )
            ->assertJsonPath(
                'operational.pending_tasks.0.priority',
                'high',
            )
            ->assertJsonPath(
                'operational.pending_tasks.0.folder.id',
                $folder->id,
            )
            ->assertJsonStructure([
                'operational' => [
                    'upcoming_events' => [
                        '*' => [
                            'id',
                            'type',
                            'title',
                            'starts_at',
                            'ends_at',
                            'location',
                            'folder' => [
                                'id',
                                'name',
                                'process_number',
                            ],
                        ],
                    ],

                    'pending_deadlines' => [
                        '*' => [
                            'id',
                            'title',
                            'due_at',
                            'status',
                            'folder' => [
                                'id',
                                'name',
                                'process_number',
                            ],
                        ],
                    ],

                    'pending_tasks' => [
                        '*' => [
                            'id',
                            'title',
                            'priority',
                            'due_at',
                            'status',
                            'folder' => [
                                'id',
                                'name',
                                'process_number',
                            ],
                        ],
                    ],
                ],
            ]);

        /*
         * As verificações de ausência são restritas
         * às respectivas coleções operacionais.
         *
         * Itens concluídos podem aparecer legitimamente
         * em recent_activity.
         */

        $upcomingEventTitles =
            collect(
                $response->json(
                    'operational.upcoming_events',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $pendingDeadlineTitles =
            collect(
                $response->json(
                    'operational.pending_deadlines',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $pendingTaskTitles =
            collect(
                $response->json(
                    'operational.pending_tasks',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $this->assertNotContains(
            'Evento passado',
            $upcomingEventTitles,
        );

        $this->assertNotContains(
            'Prazo concluído',
            $pendingDeadlineTitles,
        );

        $this->assertNotContains(
            'Tarefa concluída',
            $pendingTaskTitles,
        );
    }

    private function loginAsSuperAdmin(): string
    {
        $user =
            User::query()
            ->where(
                'email',
                'super-admin@legalis.local',
            )
            ->firstOrFail();

        return auth('api')->login(
            $user
        );
    }

    private function attachUser(
        User $user,
        Organization $organization,
        string $status = 'active',
    ): void {
        $organization
            ->users()
            ->syncWithoutDetaching([
                $user->id => [
                    'status' =>
                    $status,

                    'joined_at' =>
                    now(),
                ],
            ]);
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
