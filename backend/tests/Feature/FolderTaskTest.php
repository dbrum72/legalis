<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderTaskTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Folder $folder;

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

        $this->folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta com tarefas',

                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);
    }

    public function test_listagem_de_tarefas_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                $this->tasksUrl()
            )
            ->assertUnauthorized();
    }

    public function test_listagem_de_tarefas_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                $this->tasksUrl()
            )
            ->assertBadRequest();
    }

    public function test_cria_tarefa_na_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->tasksUrl(),
                [
                    'title' =>
                    'Revisar contestação',

                    'description' =>
                    'Revisar a minuta antes do protocolo.',

                    'priority' =>
                    'high',

                    'due_at' =>
                    '2026-08-25 18:00:00',
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'folder_id',
                $this->folder->id,
            )
            ->assertJsonPath(
                'title',
                'Revisar contestação',
            )
            ->assertJsonPath(
                'description',
                'Revisar a minuta antes do protocolo.',
            )
            ->assertJsonPath(
                'priority',
                'high',
            )
            ->assertJsonPath(
                'status',
                'pending',
            )
            ->assertJsonPath(
                'completed_at',
                null,
            )
            ->assertJsonPath(
                'user.name',
                'Super Admin',
            )
            ->assertJsonStructure([
                'id',
                'folder_id',
                'user_id',
                'title',
                'description',
                'priority',
                'due_at',
                'status',
                'completed_at',
                'created_at',
                'updated_at',

                'user' => [
                    'id',
                    'name',
                ],
            ]);

        $this->assertDatabaseHas(
            'folder_tasks',
            [
                'folder_id' =>
                $this->folder->id,

                'title' =>
                'Revisar contestação',

                'priority' =>
                'high',

                'status' =>
                'pending',
            ],
        );
    }

    public function test_tarefa_exige_titulo(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->tasksUrl(),
                [
                    'title' =>
                    '',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
            ]);
    }

    public function test_tarefa_valida_prioridade(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->tasksUrl(),
                [
                    'title' =>
                    'Analisar documentos',

                    'priority' =>
                    'urgent',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'priority',
            ]);
    }

    public function test_lista_tarefas_pendentes_antes_das_concluidas(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $completed =
            $this
            ->createTask(
                $token,
                [
                    'title' =>
                    'Tarefa concluída',

                    'priority' =>
                    'medium',
                ],
            )
            ->assertCreated();

        $completedId =
            $completed->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->patchJson(
                $this->completeUrl(
                    $completedId
                )
            )
            ->assertOk();

        $this
            ->createTask(
                $token,
                [
                    'title' =>
                    'Tarefa pendente',

                    'priority' =>
                    'high',

                    'due_at' =>
                    '2026-08-20 12:00:00',
                ],
            )
            ->assertCreated();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->getJson(
                $this->tasksUrl()
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                2
            )
            ->assertJsonPath(
                '0.title',
                'Tarefa pendente',
            )
            ->assertJsonPath(
                '0.status',
                'pending',
            )
            ->assertJsonPath(
                '1.title',
                'Tarefa concluída',
            )
            ->assertJsonPath(
                '1.status',
                'completed',
            );
    }

    public function test_conclui_tarefa(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $task =
            $this
            ->createTask(
                $token,
                [
                    'title' =>
                    'Telefonar para o cliente',

                    'priority' =>
                    'medium',
                ],
            )
            ->assertCreated();

        $taskId =
            $task->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->patchJson(
                $this->completeUrl(
                    $taskId
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'status',
                'completed',
            )
            ->assertJsonPath(
                'completed_at',
                fn($value) =>
                $value !== null
            );

        $this->assertDatabaseHas(
            'folder_tasks',
            [
                'id' =>
                $taskId,

                'status' =>
                'completed',
            ],
        );
    }

    public function test_exclui_tarefa(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $task =
            $this
            ->createTask(
                $token,
                [
                    'title' =>
                    'Tarefa temporária',

                    'priority' =>
                    'low',
                ],
            )
            ->assertCreated();

        $taskId =
            $task->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->deleteJson(
                $this->taskUrl(
                    $taskId
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folder_tasks',
            [
                'id' =>
                $taskId,
            ],
        );
    }

    public function test_tarefas_ficam_isoladas_por_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->createTask(
                $token,
                [
                    'title' =>
                    'Tarefa interna',

                    'priority' =>
                    'medium',
                ],
            )
            ->assertCreated();

        $otherOrganization =
            Organization::factory()
            ->create();

        $otherFolder =
            $otherOrganization
            ->folders()
            ->create([
                'name' =>
                'Outra pasta',

                'process_number' =>
                null,
            ]);

        $this
            ->asTenant(
                $token,
            )
            ->getJson(
                "/api/folders/{$otherFolder->id}/tasks"
            )
            ->assertNotFound();
    }

    public function test_nao_conclui_tarefa_de_outra_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $task =
            $this
            ->createTask(
                $token,
                [
                    'title' =>
                    'Tarefa da pasta principal',

                    'priority' =>
                    'high',
                ],
            )
            ->assertCreated();

        $taskId =
            $task->json(
                'id'
            );

        $otherFolder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Segunda pasta',

                'process_number' =>
                null,
            ]);

        $this
            ->asTenant(
                $token,
            )
            ->patchJson(
                "/api/folders/{$otherFolder->id}/tasks/{$taskId}/complete"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'folder_tasks',
            [
                'id' =>
                $taskId,

                'status' =>
                'pending',
            ],
        );
    }

    private function createTask(
        string $token,
        array $payload,
    ) {
        return $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->tasksUrl(),
                $payload,
            );
    }

    private function tasksUrl(): string
    {
        return "/api/folders/{$this->folder->id}/tasks";
    }

    private function taskUrl(
        int|string $taskId,
    ): string {
        return "{$this->tasksUrl()}/{$taskId}";
    }

    private function completeUrl(
        int|string $taskId,
    ): string {
        return "{$this->taskUrl($taskId)}/complete";
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

        return auth(
            'api'
        )->login(
            $user
        );
    }

    private function asTenant(
        string $token,
    ): static {
        return $this
            ->withToken(
                $token
            )
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            );
    }
}
