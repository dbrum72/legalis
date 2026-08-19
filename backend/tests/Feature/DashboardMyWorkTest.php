<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardMyWorkTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::create(
                2026,
                8,
                19,
                16,
                30,
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

        $this->otherUser =
            User::factory()
            ->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_retorna_meu_trabalho(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta do meu trabalho',

                'process_number' =>
                '5001234-56.2026.8.21.0022',
            ]);

        $task =
            $folder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Revisar documentos',

                'priority' =>
                'high',

                'due_at' =>
                now()
                    ->copy()
                    ->addHours(2),

                'status' =>
                'pending',
            ]);

        $deadline =
            $folder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Protocolar manifestação',

                'due_at' =>
                now()
                    ->copy()
                    ->addDay(),

                'status' =>
                'pending',
            ]);

        $event =
            $folder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'hearing',

                'title' =>
                'Audiência de instrução',

                'starts_at' =>
                now()
                    ->copy()
                    ->addDays(2),

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
            ->assertJsonStructure([
                'my_work' => [
                    'pending_tasks',
                    'pending_deadlines',
                    'upcoming_events',
                ],
            ])
            ->assertJsonCount(
                1,
                'my_work.pending_tasks',
            )
            ->assertJsonCount(
                1,
                'my_work.pending_deadlines',
            )
            ->assertJsonCount(
                1,
                'my_work.upcoming_events',
            )
            ->assertJsonPath(
                'my_work.pending_tasks.0.id',
                $task->id,
            )
            ->assertJsonPath(
                'my_work.pending_tasks.0.title',
                'Revisar documentos',
            )
            ->assertJsonPath(
                'my_work.pending_tasks.0.folder.id',
                $folder->id,
            )
            ->assertJsonPath(
                'my_work.pending_deadlines.0.id',
                $deadline->id,
            )
            ->assertJsonPath(
                'my_work.pending_deadlines.0.title',
                'Protocolar manifestação',
            )
            ->assertJsonPath(
                'my_work.pending_deadlines.0.folder.id',
                $folder->id,
            )
            ->assertJsonPath(
                'my_work.upcoming_events.0.id',
                $event->id,
            )
            ->assertJsonPath(
                'my_work.upcoming_events.0.title',
                'Audiência de instrução',
            )
            ->assertJsonPath(
                'my_work.upcoming_events.0.folder.id',
                $folder->id,
            );
    }

    public function test_meu_trabalho_retorna_somente_itens_do_usuario_autenticado(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta compartilhada',

                'process_number' =>
                null,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Itens do usuário autenticado
        |--------------------------------------------------------------------------
        */

        $folder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Minha tarefa',

                'priority' =>
                'high',

                'due_at' =>
                now()
                    ->copy()
                    ->addHour(),

                'status' =>
                'pending',
            ]);

        $folder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Meu prazo',

                'due_at' =>
                now()
                    ->copy()
                    ->addHours(2),

                'status' =>
                'pending',
            ]);

        $folder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'meeting',

                'title' =>
                'Meu compromisso',

                'starts_at' =>
                now()
                    ->copy()
                    ->addHours(3),

                'status' =>
                'scheduled',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Itens de outro usuário
        |--------------------------------------------------------------------------
        |
        | Eles podem aparecer legitimamente nas coleções organizacionais
        | do Dashboard. O contrato deste teste é apenas garantir que não
        | apareçam em my_work.
        |
        */

        $folder
            ->tasks()
            ->create([
                'user_id' =>
                $this->otherUser->id,

                'title' =>
                'Tarefa de outro usuário',

                'priority' =>
                'high',

                'due_at' =>
                now()
                    ->copy()
                    ->addMinutes(30),

                'status' =>
                'pending',
            ]);

        $folder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->otherUser->id,

                'title' =>
                'Prazo de outro usuário',

                'due_at' =>
                now()
                    ->copy()
                    ->addMinutes(45),

                'status' =>
                'pending',
            ]);

        $folder
            ->events()
            ->create([
                'user_id' =>
                $this->otherUser->id,

                'type' =>
                'meeting',

                'title' =>
                'Compromisso de outro usuário',

                'starts_at' =>
                now()
                    ->copy()
                    ->addMinutes(50),

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
            ->assertJsonCount(
                1,
                'my_work.pending_tasks',
            )
            ->assertJsonCount(
                1,
                'my_work.pending_deadlines',
            )
            ->assertJsonCount(
                1,
                'my_work.upcoming_events',
            )
            ->assertJsonPath(
                'my_work.pending_tasks.0.title',
                'Minha tarefa',
            )
            ->assertJsonPath(
                'my_work.pending_deadlines.0.title',
                'Meu prazo',
            )
            ->assertJsonPath(
                'my_work.upcoming_events.0.title',
                'Meu compromisso',
            );

        /*
        |--------------------------------------------------------------------------
        | Ausência restrita a my_work
        |--------------------------------------------------------------------------
        */

        $pendingTaskTitles =
            collect(
                $response->json(
                    'my_work.pending_tasks',
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
                    'my_work.pending_deadlines',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $upcomingEventTitles =
            collect(
                $response->json(
                    'my_work.upcoming_events',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $this->assertNotContains(
            'Tarefa de outro usuário',
            $pendingTaskTitles,
        );

        $this->assertNotContains(
            'Prazo de outro usuário',
            $pendingDeadlineTitles,
        );

        $this->assertNotContains(
            'Compromisso de outro usuário',
            $upcomingEventTitles,
        );
    }

    public function test_meu_trabalho_respeita_a_organizacao_atual(): void
    {
        $currentFolder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta da organização atual',

                'process_number' =>
                null,
            ]);

        $currentFolder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Minha tarefa atual',

                'priority' =>
                'medium',

                'due_at' =>
                now()
                    ->copy()
                    ->addHours(2),

                'status' =>
                'pending',
            ]);

        $currentFolder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Meu prazo atual',

                'due_at' =>
                now()
                    ->copy()
                    ->addHours(3),

                'status' =>
                'pending',
            ]);

        $currentFolder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'hearing',

                'title' =>
                'Meu compromisso atual',

                'starts_at' =>
                now()
                    ->copy()
                    ->addHours(4),

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
                'Minha tarefa externa',

                'priority' =>
                'high',

                'due_at' =>
                now()
                    ->copy()
                    ->addMinutes(30),

                'status' =>
                'pending',
            ]);

        $otherFolder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Meu prazo externo',

                'due_at' =>
                now()
                    ->copy()
                    ->addMinutes(45),

                'status' =>
                'pending',
            ]);

        $otherFolder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'meeting',

                'title' =>
                'Meu compromisso externo',

                'starts_at' =>
                now()
                    ->copy()
                    ->addMinutes(50),

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
            ->assertJsonCount(
                1,
                'my_work.pending_tasks',
            )
            ->assertJsonCount(
                1,
                'my_work.pending_deadlines',
            )
            ->assertJsonCount(
                1,
                'my_work.upcoming_events',
            )
            ->assertJsonPath(
                'my_work.pending_tasks.0.title',
                'Minha tarefa atual',
            )
            ->assertJsonPath(
                'my_work.pending_deadlines.0.title',
                'Meu prazo atual',
            )
            ->assertJsonPath(
                'my_work.upcoming_events.0.title',
                'Meu compromisso atual',
            )
            ->assertJsonMissing([
                'title' =>
                'Minha tarefa externa',
            ])
            ->assertJsonMissing([
                'title' =>
                'Meu prazo externo',
            ])
            ->assertJsonMissing([
                'title' =>
                'Meu compromisso externo',
            ])
            ->assertJsonMissing([
                'name' =>
                'Pasta externa',
            ]);
    }

    public function test_meu_trabalho_ignora_itens_finalizados_e_compromissos_passados(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com itens históricos',

                'process_number' =>
                null,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Itens históricos
        |--------------------------------------------------------------------------
        |
        | Os concluídos devem permanecer disponíveis em recent_activity,
        | mas não podem integrar my_work.
        |
        */

        $folder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Tarefa concluída',

                'priority' =>
                'medium',

                'due_at' =>
                now()
                    ->copy()
                    ->subHour(),

                'status' =>
                'completed',

                'completed_at' =>
                now()
                    ->copy()
                    ->subMinutes(30),
            ]);

        $folder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Prazo concluído',

                'due_at' =>
                now()
                    ->copy()
                    ->subHour(),

                'status' =>
                'completed',

                'completed_at' =>
                now()
                    ->copy()
                    ->subMinutes(20),
            ]);

        $folder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'meeting',

                'title' =>
                'Compromisso passado',

                'starts_at' =>
                now()
                    ->copy()
                    ->subHour(),

                'status' =>
                'scheduled',
            ]);

        $folder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'hearing',

                'title' =>
                'Compromisso concluído',

                'starts_at' =>
                now()
                    ->copy()
                    ->addHour(),

                'status' =>
                'completed',

                'completed_at' =>
                now(),
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
            ->assertJsonCount(
                0,
                'my_work.pending_tasks',
            )
            ->assertJsonCount(
                0,
                'my_work.pending_deadlines',
            )
            ->assertJsonCount(
                0,
                'my_work.upcoming_events',
            );

        /*
        |--------------------------------------------------------------------------
        | Ausência restrita a my_work
        |--------------------------------------------------------------------------
        */

        $pendingTaskTitles =
            collect(
                $response->json(
                    'my_work.pending_tasks',
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
                    'my_work.pending_deadlines',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $upcomingEventTitles =
            collect(
                $response->json(
                    'my_work.upcoming_events',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $this->assertNotContains(
            'Tarefa concluída',
            $pendingTaskTitles,
        );

        $this->assertNotContains(
            'Prazo concluído',
            $pendingDeadlineTitles,
        );

        $this->assertNotContains(
            'Compromisso passado',
            $upcomingEventTitles,
        );

        $this->assertNotContains(
            'Compromisso concluído',
            $upcomingEventTitles,
        );
    }

    public function test_meu_trabalho_limita_cada_colecao_a_cinco_itens(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com muitos itens pessoais',

                'process_number' =>
                null,
            ]);

        foreach (
            range(
                1,
                6,
            ) as $index
        ) {
            $folder
                ->tasks()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    "Minha tarefa {$index}",

                    'priority' =>
                    'medium',

                    'due_at' =>
                    now()
                        ->copy()
                        ->addDays(
                            $index
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
                    "Meu prazo {$index}",

                    'due_at' =>
                    now()
                        ->copy()
                        ->addDays(
                            $index
                        ),

                    'status' =>
                    'pending',
                ]);

            $folder
                ->events()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'type' =>
                    'meeting',

                    'title' =>
                    "Meu compromisso {$index}",

                    'starts_at' =>
                    now()
                        ->copy()
                        ->addDays(
                            $index
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
            ->assertJsonCount(
                5,
                'my_work.pending_tasks',
            )
            ->assertJsonCount(
                5,
                'my_work.pending_deadlines',
            )
            ->assertJsonCount(
                5,
                'my_work.upcoming_events',
            )
            ->assertJsonPath(
                'my_work.pending_tasks.0.title',
                'Minha tarefa 1',
            )
            ->assertJsonPath(
                'my_work.pending_tasks.4.title',
                'Minha tarefa 5',
            )
            ->assertJsonPath(
                'my_work.pending_deadlines.0.title',
                'Meu prazo 1',
            )
            ->assertJsonPath(
                'my_work.pending_deadlines.4.title',
                'Meu prazo 5',
            )
            ->assertJsonPath(
                'my_work.upcoming_events.0.title',
                'Meu compromisso 1',
            )
            ->assertJsonPath(
                'my_work.upcoming_events.4.title',
                'Meu compromisso 5',
            );

        /*
        |--------------------------------------------------------------------------
        | O sexto item deve estar fora de cada coleção pessoal
        |--------------------------------------------------------------------------
        */

        $pendingTaskTitles =
            collect(
                $response->json(
                    'my_work.pending_tasks',
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
                    'my_work.pending_deadlines',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $upcomingEventTitles =
            collect(
                $response->json(
                    'my_work.upcoming_events',
                    [],
                )
            )
            ->pluck(
                'title'
            )
            ->all();

        $this->assertNotContains(
            'Minha tarefa 6',
            $pendingTaskTitles,
        );

        $this->assertNotContains(
            'Meu prazo 6',
            $pendingDeadlineTitles,
        );

        $this->assertNotContains(
            'Meu compromisso 6',
            $upcomingEventTitles,
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
