<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AgendaTest extends TestCase
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
                20,
                10,
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

    public function test_agenda_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                '/api/agenda?start=2026-08-01&end=2026-08-31'
            )
            ->assertUnauthorized();
    }

    public function test_agenda_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                '/api/agenda?start=2026-08-01&end=2026-08-31'
            )
            ->assertBadRequest();
    }

    public function test_agenda_consolida_tarefas_prazos_e_compromissos(): void
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
                Carbon::create(
                    2026,
                    8,
                    10,
                    14,
                    0,
                    0,
                    config('app.timezone'),
                ),

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
                Carbon::create(
                    2026,
                    8,
                    21,
                    23,
                    59,
                    59,
                    config('app.timezone'),
                ),

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
                Carbon::create(
                    2026,
                    8,
                    25,
                    14,
                    0,
                    0,
                    config('app.timezone'),
                ),

                'ends_at' =>
                Carbon::create(
                    2026,
                    8,
                    25,
                    15,
                    0,
                    0,
                    config('app.timezone'),
                ),

                'location' =>
                'Fórum de Pelotas',

                'status' =>
                'scheduled',
            ]);

        $response =
            $this
            ->withToken(
                $token
            )
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                '/api/agenda?start=2026-08-01&end=2026-08-31'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'period.start',
                '2026-08-01',
            )
            ->assertJsonPath(
                'period.end',
                '2026-08-31',
            )
            ->assertJsonCount(
                3,
                'items',
            );

        $items =
            collect(
                $response->json(
                    'items'
                )
            );

        $taskItem =
            $items
            ->first(
                fn(array $item): bool =>
                $item['type'] === 'task'
                    && $item['id'] === $task->id
            );

        $deadlineItem =
            $items
            ->first(
                fn(array $item): bool =>
                $item['type'] === 'deadline'
                    && $item['id'] === $deadline->id
            );

        $eventItem =
            $items
            ->first(
                fn(array $item): bool =>
                $item['type'] === 'event'
                    && $item['id'] === $event->id
            );

        $this->assertNotNull(
            $taskItem
        );

        $this->assertNotNull(
            $deadlineItem
        );

        $this->assertNotNull(
            $eventItem
        );

        /*
        |--------------------------------------------------------------------------
        | Tarefa
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'Revisar documentos',
            $taskItem['title'],
        );

        $this->assertSame(
            'pending',
            $taskItem['status'],
        );

        $this->assertSame(
            'high',
            $taskItem['priority'],
        );

        $this->assertArrayHasKey(
            'starts_at',
            $taskItem,
        );

        /*
        |--------------------------------------------------------------------------
        | Prazo
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'Protocolar manifestação',
            $deadlineItem['title'],
        );

        $this->assertSame(
            'pending',
            $deadlineItem['status'],
        );

        $this->assertArrayHasKey(
            'starts_at',
            $deadlineItem,
        );

        /*
        |--------------------------------------------------------------------------
        | Compromisso
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            'Audiência de instrução',
            $eventItem['title'],
        );

        $this->assertSame(
            'scheduled',
            $eventItem['status'],
        );

        $this->assertSame(
            'hearing',
            $eventItem['event_type'],
        );

        $this->assertSame(
            'Fórum de Pelotas',
            $eventItem['location'],
        );

        $this->assertArrayHasKey(
            'starts_at',
            $eventItem,
        );

        $this->assertArrayHasKey(
            'ends_at',
            $eventItem,
        );

        /*
        |--------------------------------------------------------------------------
        | Pasta
        |--------------------------------------------------------------------------
        */

        foreach (
            [
                $taskItem,
                $deadlineItem,
                $eventItem,
            ] as $item
        ) {
            $this->assertSame(
                $folder->id,
                $item['folder']['id'],
            );

            $this->assertSame(
                'Ação indenizatória',
                $item['folder']['name'],
            );

            $this->assertSame(
                '5000000-00.2026.8.21.0001',
                $item['folder']['process_number'],
            );
        }
    }

    public function test_agenda_respeita_intervalo_solicitado(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com agenda',

                'process_number' =>
                null,
            ]);

        $folder
            ->tasks()
            ->create([
                'user_id' =>
                $this->user->id,

                'title' =>
                'Tarefa de agosto',

                'priority' =>
                'medium',

                'due_at' =>
                Carbon::create(
                    2026,
                    8,
                    15,
                    10,
                    0,
                    0,
                    config('app.timezone'),
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
                'Tarefa de setembro',

                'priority' =>
                'medium',

                'due_at' =>
                Carbon::create(
                    2026,
                    9,
                    5,
                    10,
                    0,
                    0,
                    config('app.timezone'),
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
                'Prazo anterior',

                'due_at' =>
                Carbon::create(
                    2026,
                    7,
                    31,
                    23,
                    59,
                    59,
                    config('app.timezone'),
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
                'hearing',

                'title' =>
                'Audiência de agosto',

                'starts_at' =>
                Carbon::create(
                    2026,
                    8,
                    25,
                    14,
                    0,
                    0,
                    config('app.timezone'),
                ),

                'ends_at' =>
                null,

                'location' =>
                null,

                'status' =>
                'scheduled',
            ]);

        $response =
            $this
            ->withToken(
                $token
            )
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                '/api/agenda?start=2026-08-01&end=2026-08-31'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'items',
            )
            ->assertJsonFragment([
                'title' =>
                'Tarefa de agosto',
            ])
            ->assertJsonFragment([
                'title' =>
                'Audiência de agosto',
            ])
            ->assertJsonMissing([
                'title' =>
                'Tarefa de setembro',
            ])
            ->assertJsonMissing([
                'title' =>
                'Prazo anterior',
            ]);
    }

    public function test_agenda_isola_itens_por_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        /*
        |--------------------------------------------------------------------------
        | Organização atual
        |--------------------------------------------------------------------------
        */

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
                'Tarefa da organização atual',

                'priority' =>
                'high',

                'due_at' =>
                Carbon::create(
                    2026,
                    8,
                    20,
                    15,
                    0,
                    0,
                    config('app.timezone'),
                ),

                'status' =>
                'pending',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Outra organização
        |--------------------------------------------------------------------------
        */

        $otherOrganization =
            Organization::factory()
            ->create([
                'name' =>
                'Outra organização',

                'slug' =>
                'outra-organizacao',

                'status' =>
                'active',
            ]);

        $otherUser =
            User::factory()
            ->create();

        $otherOrganization
            ->users()
            ->attach(
                $otherUser->id,
                [
                    'status' =>
                    'active',

                    'joined_at' =>
                    now(),
                ],
            );

        $otherFolder =
            $otherOrganization
            ->folders()
            ->create([
                'name' =>
                'Pasta de outra organização',

                'process_number' =>
                null,
            ]);

        $otherFolder
            ->tasks()
            ->create([
                'user_id' =>
                $otherUser->id,

                'title' =>
                'Tarefa de outra organização',

                'priority' =>
                'high',

                'due_at' =>
                Carbon::create(
                    2026,
                    8,
                    20,
                    16,
                    0,
                    0,
                    config('app.timezone'),
                ),

                'status' =>
                'pending',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Request
        |--------------------------------------------------------------------------
        */

        $response =
            $this
            ->withToken(
                $token
            )
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                '/api/agenda?start=2026-08-01&end=2026-08-31'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'items',
            )
            ->assertJsonFragment([
                'title' =>
                'Tarefa da organização atual',
            ])
            ->assertJsonMissing([
                'title' =>
                'Tarefa de outra organização',
            ]);
    }

    private function loginAsSuperAdmin(): string
    {
        return auth('api')->login(
            $this->user
        );
    }
}
