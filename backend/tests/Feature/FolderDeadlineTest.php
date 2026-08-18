<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderDeadlineTest extends TestCase
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
                'Pasta com prazo',

                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);
    }

    public function test_listagem_de_prazos_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                $this->deadlinesUrl()
            )
            ->assertUnauthorized();
    }

    public function test_listagem_de_prazos_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                $this->deadlinesUrl()
            )
            ->assertBadRequest();
    }

    public function test_cria_prazo_na_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->deadlinesUrl(),
                [
                    'title' =>
                    'Apresentar contestação',

                    'description' =>
                    'Prazo para apresentação de contestação.',

                    'due_at' =>
                    '2026-08-25 23:59:00',
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
                'Apresentar contestação',
            )
            ->assertJsonPath(
                'description',
                'Prazo para apresentação de contestação.',
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
            'folder_deadlines',
            [
                'folder_id' =>
                $this->folder->id,

                'title' =>
                'Apresentar contestação',

                'status' =>
                'pending',
            ],
        );
    }

    public function test_prazo_exige_titulo_e_vencimento(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->deadlinesUrl(),
                [
                    'title' =>
                    '',

                    'due_at' =>
                    null,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'due_at',
            ]);
    }

    public function test_lista_prazos_em_ordem_de_vencimento(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->createDeadline(
                $token,
                [
                    'title' =>
                    'Prazo distante',

                    'due_at' =>
                    '2026-08-30 12:00:00',
                ],
            )
            ->assertCreated();

        $this
            ->createDeadline(
                $token,
                [
                    'title' =>
                    'Prazo imediato',

                    'due_at' =>
                    '2026-08-20 12:00:00',
                ],
            )
            ->assertCreated();

        $this
            ->createDeadline(
                $token,
                [
                    'title' =>
                    'Prazo intermediário',

                    'due_at' =>
                    '2026-08-25 12:00:00',
                ],
            )
            ->assertCreated();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->getJson(
                $this->deadlinesUrl()
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                3
            )
            ->assertJsonPath(
                '0.title',
                'Prazo imediato',
            )
            ->assertJsonPath(
                '1.title',
                'Prazo intermediário',
            )
            ->assertJsonPath(
                '2.title',
                'Prazo distante',
            );
    }

    public function test_conclui_prazo(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $deadline =
            $this
            ->createDeadline(
                $token,
                [
                    'title' =>
                    'Protocolar petição',

                    'due_at' =>
                    '2026-08-25 18:00:00',
                ],
            )
            ->assertCreated();

        $deadlineId =
            $deadline->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->patchJson(
                $this->completeUrl(
                    $deadlineId
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
            'folder_deadlines',
            [
                'id' =>
                $deadlineId,

                'status' =>
                'completed',
            ],
        );
    }

    public function test_exclui_prazo(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $deadline =
            $this
            ->createDeadline(
                $token,
                [
                    'title' =>
                    'Prazo temporário',

                    'due_at' =>
                    '2026-08-22 10:00:00',
                ],
            )
            ->assertCreated();

        $deadlineId =
            $deadline->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->deleteJson(
                $this->deadlineUrl(
                    $deadlineId
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folder_deadlines',
            [
                'id' =>
                $deadlineId,
            ],
        );
    }

    public function test_prazos_ficam_isolados_por_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->createDeadline(
                $token,
                [
                    'title' =>
                    'Prazo interno',

                    'due_at' =>
                    '2026-08-25 10:00:00',
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
                "/api/folders/{$otherFolder->id}/deadlines"
            )
            ->assertNotFound();
    }

    public function test_nao_conclui_prazo_de_outra_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $deadline =
            $this
            ->createDeadline(
                $token,
                [
                    'title' =>
                    'Prazo da pasta principal',

                    'due_at' =>
                    '2026-08-25 10:00:00',
                ],
            )
            ->assertCreated();

        $deadlineId =
            $deadline->json(
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
                "/api/folders/{$otherFolder->id}/deadlines/{$deadlineId}/complete"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'folder_deadlines',
            [
                'id' =>
                $deadlineId,

                'status' =>
                'pending',
            ],
        );
    }

    private function createDeadline(
        string $token,
        array $payload,
    ) {
        return $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->deadlinesUrl(),
                $payload,
            );
    }

    private function deadlinesUrl(): string
    {
        return "/api/folders/{$this->folder->id}/deadlines";
    }

    private function deadlineUrl(
        int|string $deadlineId,
    ): string {
        return "{$this->deadlinesUrl()}/{$deadlineId}";
    }

    private function completeUrl(
        int|string $deadlineId,
    ): string {
        return "{$this->deadlineUrl($deadlineId)}/complete";
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
