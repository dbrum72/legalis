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

    public function test_dashboard_retorna_agenda_do_dia_consolidada(): void
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
                'Ação indenizatória',

            'process_number' =>
                '5001234-56.2026.8.21.0022',
        ]);

    /*
     * Tarefa pendente para hoje.
     */

    $task =
        $folder
        ->tasks()
        ->create([
            'user_id' =>
                $user->id,

            'title' =>
                'Revisar contestação',

            'priority' =>
                'high',

            'due_at' =>
                now()
                    ->copy()
                    ->addHour(),

            'status' =>
                'pending',
        ]);

    /*
     * Prazo pendente para hoje.
     */

    $deadline =
        $folder
        ->deadlines()
        ->create([
            'user_id' =>
                $user->id,

            'title' =>
                'Protocolar manifestação',

            'due_at' =>
                now()
                    ->copy()
                    ->addHours(2),

            'status' =>
                'pending',
        ]);

    /*
     * Compromisso restante de hoje.
     */

    $event =
        $folder
        ->events()
        ->create([
            'user_id' =>
                $user->id,

            'type' =>
                'hearing',

            'title' =>
                'Audiência de instrução',

            'starts_at' =>
                now()
                    ->copy()
                    ->addHours(3),

            'location' =>
                'Fórum de Pelotas',

            'status' =>
                'scheduled',
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
        ->assertJsonCount(
            3,
            'today_agenda',
        )
        ->assertJsonPath(
            'today_agenda.0.kind',
            'task',
        )
        ->assertJsonPath(
            'today_agenda.0.id',
            $task->id,
        )
        ->assertJsonPath(
            'today_agenda.0.title',
            'Revisar contestação',
        )
        ->assertJsonPath(
            'today_agenda.0.priority',
            'high',
        )
        ->assertJsonPath(
            'today_agenda.1.kind',
            'deadline',
        )
        ->assertJsonPath(
            'today_agenda.1.id',
            $deadline->id,
        )
        ->assertJsonPath(
            'today_agenda.1.title',
            'Protocolar manifestação',
        )
        ->assertJsonPath(
            'today_agenda.2.kind',
            'event',
        )
        ->assertJsonPath(
            'today_agenda.2.id',
            $event->id,
        )
        ->assertJsonPath(
            'today_agenda.2.title',
            'Audiência de instrução',
        )
        ->assertJsonPath(
            'today_agenda.2.type',
            'hearing',
        )
        ->assertJsonPath(
            'today_agenda.2.location',
            'Fórum de Pelotas',
        )
        ->assertJsonStructure([
            'today_agenda' => [
                '*' => [
                    'kind',
                    'id',
                    'title',
                    'scheduled_at',
                    'folder' => [
                        'id',
                        'name',
                        'process_number',
                    ],
                ],
            ],
        ]);
}


public function test_dashboard_agenda_do_dia_ignora_itens_fora_do_dia_e_concluidos(): void
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
                'Pasta agenda',

            'process_number' =>
                null,
        ]);

    /*
     * Deve aparecer.
     */

    $folder
        ->tasks()
        ->create([
            'user_id' =>
                $user->id,

            'title' =>
                'Tarefa de hoje',

            'priority' =>
                'medium',

            'due_at' =>
                now()
                    ->copy()
                    ->addHour(),

            'status' =>
                'pending',
        ]);

    /*
     * Amanhã: não aparece.
     */

    $folder
        ->tasks()
        ->create([
            'user_id' =>
                $user->id,

            'title' =>
                'Tarefa de amanhã',

            'priority' =>
                'low',

            'due_at' =>
                now()
                    ->copy()
                    ->addDay(),

            'status' =>
                'pending',
        ]);

    /*
     * Concluído: não aparece.
     */

    $folder
        ->deadlines()
        ->create([
            'user_id' =>
                $user->id,

            'title' =>
                'Prazo concluído hoje',

            'due_at' =>
                now()
                    ->copy()
                    ->addHours(2),

            'status' =>
                'completed',

            'completed_at' =>
                now(),
        ]);

    /*
     * Evento de amanhã: não aparece.
     */

    $folder
        ->events()
        ->create([
            'user_id' =>
                $user->id,

            'type' =>
                'meeting',

            'title' =>
                'Reunião de amanhã',

            'starts_at' =>
                now()
                    ->copy()
                    ->addDay(),

            'status' =>
                'scheduled',
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
        ->assertJsonCount(
            1,
            'today_agenda',
        )
        ->assertJsonPath(
            'today_agenda.0.kind',
            'task',
        )
        ->assertJsonPath(
            'today_agenda.0.title',
            'Tarefa de hoje',
        );

    $titles =
        collect(
            $response->json(
                'today_agenda',
                [],
            )
        )
        ->pluck(
            'title'
        )
        ->all();

    $this->assertNotContains(
        'Tarefa de amanhã',
        $titles,
    );

    $this->assertNotContains(
        'Prazo concluído hoje',
        $titles,
    );

    $this->assertNotContains(
        'Reunião de amanhã',
        $titles,
    );
}
}
