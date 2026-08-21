<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderTest extends TestCase
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

    public function test_index_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson('/api/folders')
            ->assertUnauthorized();
    }

    public function test_usuario_sem_permissao_nao_pode_listar_pastas(): void
    {
        $user =
            User::factory()->create();

        $this->attachUser(
            $user,
            $this->organization,
        );

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson('/api/folders')
            ->assertForbidden();
    }

    public function test_usuario_com_permissao_pode_listar_pastas(): void
    {
        $user =
            User::factory()->create();

        $this->attachUser(
            $user,
            $this->organization,
        );

        $this->givePermissionInOrganization(
            $user,
            $this->organization,
            'folders.view',
        );

        Folder::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Ação indenizatória',

                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson('/api/folders')
            ->assertOk()
            ->assertJsonFragment([
                'name' =>
                'Ação indenizatória',
            ]);
    }

    public function test_index_retorna_apenas_pastas_da_organizacao_atual(): void
    {
        Folder::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Pasta Organização A',
            ]);

        $otherOrganization =
            Organization::factory()
            ->create();

        Folder::factory()
            ->for(
                $otherOrganization
            )
            ->create([
                'name' =>
                'Pasta Organização B',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/folders'
            );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'name' =>
                'Pasta Organização A',
            ])
            ->assertJsonMissing([
                'name' =>
                'Pasta Organização B',
            ]);
    }

    public function test_cria_pasta_na_organizacao_atual(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                '/api/folders',
                [
                    'name' =>
                    'Ação indenizatória',

                    'process_number' =>
                    '5000000-00.2026.8.21.0001',
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'name',
                'Ação indenizatória',
            )
            ->assertJsonPath(
                'organization_id',
                $this->organization->id,
            );

        $this->assertDatabaseHas(
            'folders',
            [
                'organization_id' =>
                $this->organization->id,

                'name' =>
                'Ação indenizatória',
            ],
        );
    }

    public function test_payload_nao_pode_escolher_organization_id(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                '/api/folders',
                [
                    'organization_id' =>
                    $otherOrganization->id,

                    'name' =>
                    'Pasta Teste',
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'organization_id',
                $this->organization->id,
            );

        $this->assertDatabaseHas(
            'folders',
            [
                'organization_id' =>
                $this->organization->id,

                'name' =>
                'Pasta Teste',
            ],
        );

        $this->assertDatabaseMissing(
            'folders',
            [
                'organization_id' =>
                $otherOrganization->id,

                'name' =>
                'Pasta Teste',
            ],
        );
    }

    public function test_nome_e_obrigatorio(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                '/api/folders',
                [
                    'process_number' =>
                    '5000000-00.2026.8.21.0001',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    public function test_process_number_pode_ser_null(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                '/api/folders',
                [
                    'name' =>
                    'Atendimento extrajudicial',

                    'process_number' =>
                    null,
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'process_number',
                null,
            );
    }

    public function test_exibe_pasta(): void
    {
        $folder =
            Folder::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Ação de cobrança',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/folders/{$folder->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'id',
                $folder->id,
            );
    }

    public function test_exibe_resumo_operacional_da_pasta(): void
    {
        $folder =
            Folder::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Ação indenizatória',
            ]);

        $folder
            ->documents()
            ->createMany([
                [
                    'name' =>
                    'Petição inicial.pdf',

                    'original_name' =>
                    'Petição inicial.pdf',

                    'path' =>
                    'folders/test/peticao-inicial.pdf',

                    'mime_type' =>
                    'application/pdf',

                    'size' =>
                    1024,

                    'description' =>
                    null,
                ],
                [
                    'name' =>
                    'Contestação.pdf',

                    'original_name' =>
                    'Contestação.pdf',

                    'path' =>
                    'folders/test/contestacao.pdf',

                    'mime_type' =>
                    'application/pdf',

                    'size' =>
                    2048,

                    'description' =>
                    null,
                ],
            ]);

        $folder
            ->tasks()
            ->createMany([
                [
                    'title' =>
                    'Analisar contestação',

                    'description' =>
                    null,

                    'priority' =>
                    'high',

                    'due_at' =>
                    null,

                    'status' =>
                    'pending',

                    'completed_at' =>
                    null,
                ],
                [
                    'title' =>
                    'Preparar réplica',

                    'description' =>
                    null,

                    'priority' =>
                    'medium',

                    'due_at' =>
                    null,

                    'status' =>
                    'pending',

                    'completed_at' =>
                    null,
                ],
                [
                    'title' =>
                    'Telefonar para cliente',

                    'description' =>
                    null,

                    'priority' =>
                    'low',

                    'due_at' =>
                    null,

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now(),
                ],
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/folders/{$folder->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'summary.documents_count',
                2,
            )
            ->assertJsonPath(
                'summary.pending_tasks_count',
                2,
            );
    }

    public function test_exibe_destaques_operacionais_da_pasta(): void
    {
        $folder =
            Folder::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Ação indenizatória',
            ]);

        $folder
            ->deadlines()
            ->createMany([
                [
                    'title' =>
                    'Apresentar réplica',

                    'description' =>
                    null,

                    'due_at' =>
                    now()->addDays(5),

                    'status' =>
                    'pending',

                    'completed_at' =>
                    null,
                ],
                [
                    'title' =>
                    'Prazo já concluído',

                    'description' =>
                    null,

                    'due_at' =>
                    now()->addDays(2),

                    'status' =>
                    'completed',

                    'completed_at' =>
                    now(),
                ],
                [
                    'title' =>
                    'Protocolar memoriais',

                    'description' =>
                    null,

                    'due_at' =>
                    now()->addDays(10),

                    'status' =>
                    'pending',

                    'completed_at' =>
                    null,
                ],
            ]);

        $pastEvent =
            $folder
            ->events()
            ->create([
                'type' =>
                'meeting',

                'title' =>
                'Reunião passada',

                'description' =>
                null,

                'starts_at' =>
                now()->subDay(),

                'ends_at' =>
                null,

                'location' =>
                null,

                'status' =>
                'scheduled',

                'completed_at' =>
                null,
            ]);

        $nextEvent =
            $folder
            ->events()
            ->create([
                'type' =>
                'hearing',

                'title' =>
                'Audiência de instrução',

                'description' =>
                null,

                'starts_at' =>
                now()->addDays(3),

                'ends_at' =>
                now()->addDays(3)->addHour(),

                'location' =>
                '3ª Vara Cível',

                'status' =>
                'scheduled',

                'completed_at' =>
                null,
            ]);

        $laterEvent =
            $folder
            ->events()
            ->create([
                'type' =>
                'meeting',

                'title' =>
                'Reunião futura',

                'description' =>
                null,

                'starts_at' =>
                now()->addDays(8),

                'ends_at' =>
                null,

                'location' =>
                null,

                'status' =>
                'scheduled',

                'completed_at' =>
                null,
            ]);

        $completedEvent =
            $folder
            ->events()
            ->create([
                'type' =>
                'diligence',

                'title' =>
                'Compromisso concluído',

                'description' =>
                null,

                'starts_at' =>
                now()->addDay(),

                'ends_at' =>
                null,

                'location' =>
                null,

                'status' =>
                'completed',

                'completed_at' =>
                now(),
            ]);

        $olderMovement =
            $folder
            ->movements()
            ->create([
                'occurred_at' =>
                now()->subDays(4),

                'title' =>
                'Petição protocolada',

                'description' =>
                null,
            ]);

        $latestMovement =
            $folder
            ->movements()
            ->create([
                'occurred_at' =>
                now()->subDay(),

                'title' =>
                'Despacho publicado',

                'description' =>
                'Juízo determinou manifestação da parte autora.',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/folders/{$folder->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'summary.pending_deadlines_count',
                2,
            )
            ->assertJsonPath(
                'summary.next_event.id',
                $nextEvent->id,
            )
            ->assertJsonPath(
                'summary.next_event.title',
                'Audiência de instrução',
            )
            ->assertJsonPath(
                'summary.next_event.type',
                'hearing',
            )
            ->assertJsonPath(
                'summary.next_event.location',
                '3ª Vara Cível',
            )
            ->assertJsonPath(
                'summary.latest_movement.id',
                $latestMovement->id,
            )
            ->assertJsonPath(
                'summary.latest_movement.title',
                'Despacho publicado',
            );

        $this->assertNotSame(
            $pastEvent->id,
            $response->json(
                'summary.next_event.id'
            )
        );

        $this->assertNotSame(
            $laterEvent->id,
            $response->json(
                'summary.next_event.id'
            )
        );

        $this->assertNotSame(
            $completedEvent->id,
            $response->json(
                'summary.next_event.id'
            )
        );

        $this->assertNotSame(
            $olderMovement->id,
            $response->json(
                'summary.latest_movement.id'
            )
        );
    }

    public function test_exibe_prazos_prioritarios_no_resumo_da_pasta(): void
    {
        $this->travelTo(
            \Illuminate\Support\Carbon::parse(
                '2026-08-21 09:00:00'
            )
        );

        $folder =
            Folder::factory()
            ->for(
                $this->organization
            )
            ->create();

        $overdue =
            $folder
            ->deadlines()
            ->create([
                'title' =>
                'Apresentar réplica',

                'description' =>
                null,

                'due_at' =>
                now()->subDay(),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $today =
            $folder
            ->deadlines()
            ->create([
                'title' =>
                'Protocolar manifestação',

                'description' =>
                null,

                'due_at' =>
                now()->endOfDay(),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $upcoming =
            $folder
            ->deadlines()
            ->create([
                'title' =>
                'Juntar documentos',

                'description' =>
                null,

                'due_at' =>
                now()->addDays(2),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $folder
            ->deadlines()
            ->create([
                'title' =>
                'Prazo posterior',

                'description' =>
                null,

                'due_at' =>
                now()->addDays(5),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $folder
            ->deadlines()
            ->create([
                'title' =>
                'Prazo concluído',

                'description' =>
                null,

                'due_at' =>
                now()->subDays(3),

                'status' =>
                'completed',

                'completed_at' =>
                now(),
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/folders/{$folder->id}"
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                3,
                'summary.attention.deadlines',
            )
            ->assertJsonPath(
                'summary.attention.deadlines.0.id',
                $overdue->id,
            )
            ->assertJsonPath(
                'summary.attention.deadlines.0.urgency',
                'overdue',
            )
            ->assertJsonPath(
                'summary.attention.deadlines.1.id',
                $today->id,
            )
            ->assertJsonPath(
                'summary.attention.deadlines.1.urgency',
                'today',
            )
            ->assertJsonPath(
                'summary.attention.deadlines.2.id',
                $upcoming->id,
            )
            ->assertJsonPath(
                'summary.attention.deadlines.2.urgency',
                'upcoming',
            );
    }

    public function test_exibe_tarefas_prioritarias_no_resumo_da_pasta(): void
    {
        $this->travelTo(
            \Illuminate\Support\Carbon::parse(
                '2026-08-21 09:00:00'
            )
        );

        $folder =
            Folder::factory()
            ->for(
                $this->organization
            )
            ->create();

        $overdue =
            $folder
            ->tasks()
            ->create([
                'title' =>
                'Revisar contestação',

                'description' =>
                null,

                'priority' =>
                'high',

                'due_at' =>
                now()->subDay(),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $today =
            $folder
            ->tasks()
            ->create([
                'title' =>
                'Conferir documentos',

                'description' =>
                null,

                'priority' =>
                'medium',

                'due_at' =>
                now()->endOfDay(),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $withoutDueDate =
            $folder
            ->tasks()
            ->create([
                'title' =>
                'Contatar cliente',

                'description' =>
                null,

                'priority' =>
                'high',

                'due_at' =>
                null,

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $folder
            ->tasks()
            ->create([
                'title' =>
                'Tarefa futura de baixa prioridade',

                'description' =>
                null,

                'priority' =>
                'low',

                'due_at' =>
                now()->addDays(5),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $folder
            ->tasks()
            ->create([
                'title' =>
                'Tarefa concluída',

                'description' =>
                null,

                'priority' =>
                'high',

                'due_at' =>
                now()->subDays(2),

                'status' =>
                'completed',

                'completed_at' =>
                now(),
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/folders/{$folder->id}"
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                3,
                'summary.attention.tasks',
            )
            ->assertJsonPath(
                'summary.attention.tasks.0.id',
                $overdue->id,
            )
            ->assertJsonPath(
                'summary.attention.tasks.0.urgency',
                'overdue',
            )
            ->assertJsonPath(
                'summary.attention.tasks.0.priority',
                'high',
            )
            ->assertJsonPath(
                'summary.attention.tasks.1.id',
                $today->id,
            )
            ->assertJsonPath(
                'summary.attention.tasks.1.urgency',
                'today',
            )
            ->assertJsonPath(
                'summary.attention.tasks.2.id',
                $withoutDueDate->id,
            )
            ->assertJsonPath(
                'summary.attention.tasks.2.urgency',
                'unscheduled',
            )
            ->assertJsonPath(
                'summary.attention.tasks.2.priority',
                'high',
            );
    }

    public function test_nao_exibe_pasta_de_outra_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $folder =
            Folder::factory()
            ->for(
                $otherOrganization
            )
            ->create();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/folders/{$folder->id}"
            )
            ->assertNotFound();
    }

    public function test_atualiza_pasta(): void
    {
        $folder =
            Folder::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Nome original',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/folders/{$folder->id}",
                [
                    'name' =>
                    'Nome atualizado',

                    'process_number' =>
                    '5000002-00.2026.8.21.0001',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'name',
                'Nome atualizado',
            );
    }

    public function test_nao_atualiza_pasta_de_outra_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $folder =
            Folder::factory()
            ->for(
                $otherOrganization
            )
            ->create([
                'name' =>
                'Nome original',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/folders/{$folder->id}",
                [
                    'name' =>
                    'Tentativa de alteração',

                    'process_number' =>
                    null,
                ],
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'folders',
            [
                'id' =>
                $folder->id,

                'name' =>
                'Nome original',
            ],
        );
    }

    public function test_exclui_pasta(): void
    {
        $folder =
            Folder::factory()
            ->for(
                $this->organization
            )
            ->create();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->deleteJson(
                "/api/folders/{$folder->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folders',
            [
                'id' =>
                $folder->id,
            ],
        );
    }

    public function test_nao_exclui_pasta_de_outra_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $folder =
            Folder::factory()
            ->for(
                $otherOrganization
            )
            ->create();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->deleteJson(
                "/api/folders/{$folder->id}"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'folders',
            [
                'id' =>
                $folder->id,
            ],
        );
    }

    private function givePermissionInOrganization(
        User $user,
        Organization $organization,
        string $permission,
    ): void {
        $previousTeamId =
            getPermissionsTeamId();

        try {
            setPermissionsTeamId(
                $organization->id
            );

            $this->clearPermissionRelations(
                $user
            );

            $user->givePermissionTo(
                $permission
            );

            $this->clearPermissionRelations(
                $user
            );
        } finally {
            setPermissionsTeamId(
                $previousTeamId
            );

            $this->clearPermissionRelations(
                $user
            );
        }
    }

    private function clearPermissionRelations(
        User $user,
    ): void {
        $user->unsetRelation(
            'roles'
        );

        $user->unsetRelation(
            'permissions'
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
    ): void {
        $organization
            ->users()
            ->syncWithoutDetaching([
                $user->id => [
                    'status' =>
                    'active',
                ],
            ]);
    }

    private function asTenant(
        string $token,
        Organization $organization,
    ): static {
        return $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            );
    }
}
