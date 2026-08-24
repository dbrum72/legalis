<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardAttentionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::create(
                2026,
                8,
                19,
                14,
                0,
                0,
                config('app.timezone'),
            )
        );

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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_retorna_indicadores_e_itens_da_central_de_atencao(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com itens críticos',

                'process_number' =>
                '5001234-56.2026.8.21.0022',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tarefas
        |--------------------------------------------------------------------------
        |
        | 2 pendentes vencidas.
        | 1 pendente futura.
        | 1 vencida, porém concluída.
        |
        */

        $folder
            ->tasks()
            ->createMany([
                [
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    'Tarefa vencida ontem',

                    'priority' =>
                    'high',

                    'due_at' =>
                    now()->subDay(),

                    'status' =>
                    'pending',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    'Tarefa vencida pela manhã',

                    'priority' =>
                    'medium',

                    'due_at' =>
                    now()->subHours(2),

                    'status' =>
                    'pending',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    'Tarefa futura',

                    'priority' =>
                    'low',

                    'due_at' =>
                    now()->addDay(),

                    'status' =>
                    'pending',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    'Tarefa vencida concluída',

                    'priority' =>
                    'high',

                    'due_at' =>
                    now()->subDays(2),

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now(),
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Prazos
        |--------------------------------------------------------------------------
        |
        | 2 pendentes vencidos.
        | 1 pendente futuro.
        | 1 vencido, porém concluído.
        |
        */

        $folder
            ->deadlines()
            ->createMany([
                [
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    'Prazo vencido ontem',

                    'due_at' =>
                    now()->subDay(),

                    'status' =>
                    'pending',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    'Prazo vencido pela manhã',

                    'due_at' =>
                    now()->subHour(),

                    'status' =>
                    'pending',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    'Prazo futuro',

                    'due_at' =>
                    now()->addDays(3),

                    'status' =>
                    'pending',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    'Prazo vencido concluído',

                    'due_at' =>
                    now()->subDays(3),

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now(),
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Agenda
        |--------------------------------------------------------------------------
        |
        | 2 compromissos ainda futuros no dia de hoje.
        |
        | O compromisso de hoje cujo horário já passou
        | NÃO deve integrar today_agenda, pois seu horário já passou.
        |
        | Também ficam de fora:
        |
        | - compromisso de amanhã;
        | - compromisso de ontem;
        | - compromisso concluído.
        |
        */

        $folder
            ->events()
            ->createMany([
                [
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'hearing',

                    'title' =>
                    'Audiência hoje',

                    'starts_at' =>
                    now()->addHours(2),

                    'status' =>
                    'scheduled',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'meeting',

                    'title' =>
                    'Reunião hoje',

                    'starts_at' =>
                    now()->addHours(6),

                    'status' =>
                    'scheduled',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'meeting',

                    'title' =>
                    'Compromisso de hoje já passado',

                    'starts_at' =>
                    now()->subHours(2),

                    'status' =>
                    'scheduled',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'meeting',

                    'title' =>
                    'Reunião amanhã',

                    'starts_at' =>
                    now()->addDay(),

                    'status' =>
                    'scheduled',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'meeting',

                    'title' =>
                    'Reunião ontem',

                    'starts_at' =>
                    now()->subDay(),

                    'status' =>
                    'scheduled',
                ],

                [
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'hearing',

                    'title' =>
                    'Audiência concluída hoje',

                    'starts_at' =>
                    now()->addHour(),

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now(),
                ],
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
                'summary.overdue_tasks',
                2,
            )
            ->assertJsonPath(
                'summary.overdue_deadlines',
                2,
            );

        /*
        |--------------------------------------------------------------------------
        | Tarefas vencidas
        |--------------------------------------------------------------------------
        */

        $response
            ->assertJsonCount(
                2,
                'attention.overdue_tasks',
            )
            ->assertJsonPath(
                'attention.overdue_tasks.0.title',
                'Tarefa vencida ontem',
            )
            ->assertJsonPath(
                'attention.overdue_tasks.0.priority',
                'high',
            )
            ->assertJsonPath(
                'attention.overdue_tasks.0.status',
                'pending',
            )
            ->assertJsonPath(
                'attention.overdue_tasks.0.folder.id',
                $folder->id,
            )
            ->assertJsonPath(
                'attention.overdue_tasks.0.folder.name',
                'Pasta com itens críticos',
            )
            ->assertJsonPath(
                'attention.overdue_tasks.0.folder.process_number',
                '5001234-56.2026.8.21.0022',
            )
            ->assertJsonPath(
                'attention.overdue_tasks.1.title',
                'Tarefa vencida pela manhã',
            );

        /*
        |--------------------------------------------------------------------------
        | Prazos vencidos
        |--------------------------------------------------------------------------
        */

        $response
            ->assertJsonCount(
                2,
                'attention.overdue_deadlines',
            )
            ->assertJsonPath(
                'attention.overdue_deadlines.0.title',
                'Prazo vencido ontem',
            )
            ->assertJsonPath(
                'attention.overdue_deadlines.0.status',
                'pending',
            )
            ->assertJsonPath(
                'attention.overdue_deadlines.0.folder.id',
                $folder->id,
            )
            ->assertJsonPath(
                'attention.overdue_deadlines.0.folder.name',
                'Pasta com itens críticos',
            )
            ->assertJsonPath(
                'attention.overdue_deadlines.0.folder.process_number',
                '5001234-56.2026.8.21.0022',
            )
            ->assertJsonPath(
                'attention.overdue_deadlines.1.title',
                'Prazo vencido pela manhã',
            );
    }

    public function test_central_de_atencao_limita_cada_colecao_a_cinco_itens(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com muitos itens críticos',

                'process_number' =>
                null,
            ]);

        foreach (range(1, 6) as $index) {
            $folder
                ->tasks()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    "Tarefa vencida {$index}",

                    'priority' =>
                    'medium',

                    'due_at' =>
                    now()->subDays(
                        7 - $index
                    ),

                    'status' =>
                    'pending',
                ]);

            $folder
                ->deadlines()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    "Prazo vencido {$index}",

                    'due_at' =>
                    now()->subDays(
                        7 - $index
                    ),

                    'status' =>
                    'pending',
                ]);
        }

        foreach (range(1, 6) as $index) {
            $folder
                ->events()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'meeting',

                    'title' =>
                    "Compromisso hoje {$index}",

                    'starts_at' =>
                    now()->addMinutes(
                        $index * 30
                    ),

                    'status' =>
                    'scheduled',
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
            ->assertJsonPath(
                'summary.overdue_tasks',
                6,
            )
            ->assertJsonPath(
                'summary.overdue_deadlines',
                6,
            )
            ->assertJsonCount(
                5,
                'attention.overdue_tasks',
            )
            ->assertJsonCount(
                5,
                'attention.overdue_deadlines',
            );
    }

    public function test_itens_da_central_de_atencao_respeitam_a_organizacao_atual(): void
    {
        $currentFolder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta atual',

                'process_number' =>
                null,
            ]);

        $currentFolder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Tarefa vencida atual',

                'priority' =>
                'high',

                'due_at' =>
                now()->subHour(),

                'status' =>
                'pending',
            ]);

        $currentFolder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Prazo vencido atual',

                'due_at' =>
                now()->subHour(),

                'status' =>
                'pending',
            ]);

        $currentFolder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'meeting',

                'title' =>
                'Compromisso atual hoje',

                'starts_at' =>
                now()->addHour(),

                'status' =>
                'scheduled',
            ]);

        $otherOrganization =
            Organization::factory()
            ->create();

        $otherFolder =
            $otherOrganization
            ->folders()
            ->create([
                'name' =>
                'Pasta externa',

                'process_number' =>
                null,
            ]);

        $otherFolder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Tarefa externa vencida',

                'priority' =>
                'high',

                'due_at' =>
                now()->subHours(2),

                'status' =>
                'pending',
            ]);

        $otherFolder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Prazo externo vencido',

                'due_at' =>
                now()->subHours(2),

                'status' =>
                'pending',
            ]);

        $otherFolder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'hearing',

                'title' =>
                'Compromisso externo hoje',

                'starts_at' =>
                now()->addHours(2),

                'status' =>
                'scheduled',
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
                'summary.overdue_tasks',
                1,
            )
            ->assertJsonPath(
                'summary.overdue_deadlines',
                1,
            )
            ->assertJsonCount(
                1,
                'attention.overdue_tasks',
            )
            ->assertJsonCount(
                1,
                'attention.overdue_deadlines',
            )
            ->assertJsonPath(
                'attention.overdue_tasks.0.title',
                'Tarefa vencida atual',
            )
            ->assertJsonPath(
                'attention.overdue_deadlines.0.title',
                'Prazo vencido atual',
            );
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
