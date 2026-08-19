<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardRecentActivityTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_retorna_atividade_recente_ordenada_por_data(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com atividade recente',

                'process_number' =>
                '5001234-56.2026.8.21.0022',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tarefa concluída
        |--------------------------------------------------------------------------
        */

        $task =
            $folder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Revisar contestação',

                'priority' =>
                'high',

                'due_at' =>
                now()
                    ->copy()
                    ->subDay(),

                'status' =>
                'completed',

                'completed_at' =>
                now()
                    ->copy()
                    ->subHours(3),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Prazo concluído
        |--------------------------------------------------------------------------
        */

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
                    ->subDay(),

                'status' =>
                'completed',

                'completed_at' =>
                now()
                    ->copy()
                    ->subHours(2),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Compromisso concluído
        |--------------------------------------------------------------------------
        */

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
                    ->subDay(),

                'status' =>
                'completed',

                'completed_at' =>
                now()
                    ->copy()
                    ->subHour(),
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
                'recent_activity',
            ])
            ->assertJsonCount(
                3,
                'recent_activity',
            )
            ->assertJsonPath(
                'recent_activity.0.type',
                'event',
            )
            ->assertJsonPath(
                'recent_activity.0.id',
                $event->id,
            )
            ->assertJsonPath(
                'recent_activity.0.title',
                'Audiência de instrução',
            )
            ->assertJsonPath(
                'recent_activity.1.type',
                'deadline',
            )
            ->assertJsonPath(
                'recent_activity.1.id',
                $deadline->id,
            )
            ->assertJsonPath(
                'recent_activity.1.title',
                'Protocolar manifestação',
            )
            ->assertJsonPath(
                'recent_activity.2.type',
                'task',
            )
            ->assertJsonPath(
                'recent_activity.2.id',
                $task->id,
            )
            ->assertJsonPath(
                'recent_activity.2.title',
                'Revisar contestação',
            );
    }

    public function test_atividade_recente_retorna_dados_da_pasta(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Ação indenizatória',

                'process_number' =>
                '5009876-54.2026.8.21.0022',
            ]);

        $task =
            $folder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Conferir documentos',

                'priority' =>
                'medium',

                'due_at' =>
                now()
                    ->copy()
                    ->subDay(),

                'status' =>
                'completed',

                'completed_at' =>
                now()
                    ->copy()
                    ->subMinute(),
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
                'recent_activity',
            ])
            ->assertJsonPath(
                'recent_activity.0.id',
                $task->id,
            )
            ->assertJsonPath(
                'recent_activity.0.type',
                'task',
            )
            ->assertJsonPath(
                'recent_activity.0.folder.id',
                $folder->id,
            )
            ->assertJsonPath(
                'recent_activity.0.folder.name',
                'Ação indenizatória',
            )
            ->assertJsonPath(
                'recent_activity.0.folder.process_number',
                '5009876-54.2026.8.21.0022',
            );
    }

    public function test_atividade_recente_respeita_a_organizacao_atual(): void
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

        $currentTask =
            $currentFolder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Atividade da organização atual',

                'priority' =>
                'high',

                'due_at' =>
                now()
                    ->copy()
                    ->subDay(),

                'status' =>
                'completed',

                'completed_at' =>
                now()
                    ->copy()
                    ->subMinute(),
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
                'Atividade de outra organização',

                'priority' =>
                'high',

                'due_at' =>
                now()
                    ->copy()
                    ->subDay(),

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
            ->assertJsonStructure([
                'recent_activity',
            ])
            ->assertJsonCount(
                1,
                'recent_activity',
            )
            ->assertJsonPath(
                'recent_activity.0.id',
                $currentTask->id,
            )
            ->assertJsonPath(
                'recent_activity.0.type',
                'task',
            )
            ->assertJsonPath(
                'recent_activity.0.title',
                'Atividade da organização atual',
            );
    }

    public function test_atividade_recente_limita_o_resultado_a_dez_itens(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com muitas atividades',

                'process_number' =>
                null,
            ]);

        foreach (
            range(
                1,
                12,
            ) as $index
        ) {
            $folder
                ->tasks()
                ->create([
                    'user_id' =>
                    $this->user->id,

                    'title' =>
                    "Tarefa concluída {$index}",

                    'priority' =>
                    'medium',

                    'due_at' =>
                    now()
                        ->copy()
                        ->subDays(2),

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now()
                        ->copy()
                        ->subMinutes(
                            $index
                        ),
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
            ->assertJsonStructure([
                'recent_activity',
            ])
            ->assertJsonCount(
                10,
                'recent_activity',
            )
            ->assertJsonPath(
                'recent_activity.0.title',
                'Tarefa concluída 1',
            )
            ->assertJsonPath(
                'recent_activity.9.title',
                'Tarefa concluída 10',
            );
    }

    public function test_atividade_recente_ignora_itens_nao_concluidos(): void
    {
        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta sem atividade concluída',

                'process_number' =>
                null,
            ]);

        $folder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Tarefa pendente',

                'priority' =>
                'medium',

                'due_at' =>
                now()
                    ->copy()
                    ->addDay(),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $folder
            ->deadlines()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Prazo pendente',

                'due_at' =>
                now()
                    ->copy()
                    ->addDay(),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $folder
            ->events()
            ->create([
                'user_id' =>
                $this->user->id,

                'type' =>
                'meeting',

                'title' =>
                'Compromisso agendado',

                'starts_at' =>
                now()
                    ->copy()
                    ->addDay(),

                'status' =>
                'scheduled',

                'completed_at' =>
                null,
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
                'recent_activity',
            ])
            ->assertJsonCount(
                0,
                'recent_activity',
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
