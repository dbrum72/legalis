<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderMovementTest extends TestCase
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
                'Pasta de teste',

                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);
    }

    public function test_listagem_de_movimentacoes_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                $this->movementsUrl()
            )
            ->assertUnauthorized();
    }

    public function test_listagem_de_movimentacoes_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                $this->movementsUrl()
            )
            ->assertBadRequest();
    }

    public function test_registra_movimentacao_na_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->movementsUrl(),
                [
                    'occurred_at' =>
                    '2026-08-17 14:30:00',

                    'title' =>
                    'Petição protocolada',

                    'description' =>
                    'Petição inicial protocolada no sistema do tribunal.',
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
                'Petição protocolada',
            )
            ->assertJsonPath(
                'description',
                'Petição inicial protocolada no sistema do tribunal.',
            )
            ->assertJsonPath(
                'user.name',
                'Super Admin',
            )
            ->assertJsonStructure([
                'id',
                'folder_id',
                'user_id',
                'occurred_at',
                'title',
                'description',
                'created_at',
                'updated_at',

                'user' => [
                    'id',
                    'name',
                ],
            ]);

        $this->assertDatabaseHas(
            'folder_movements',
            [
                'folder_id' =>
                $this->folder->id,

                'title' =>
                'Petição protocolada',

                'description' =>
                'Petição inicial protocolada no sistema do tribunal.',
            ],
        );
    }

    public function test_movimentacao_exige_data_e_titulo(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->movementsUrl(),
                [
                    'occurred_at' =>
                    null,

                    'title' =>
                    '',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'occurred_at',
                'title',
            ]);
    }

    public function test_lista_movimentacoes_em_ordem_cronologica_decrescente(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->createMovement(
                $token,
                [
                    'occurred_at' =>
                    '2026-08-15 10:00:00',

                    'title' =>
                    'Movimentação antiga',
                ],
            )
            ->assertCreated();

        $this
            ->createMovement(
                $token,
                [
                    'occurred_at' =>
                    '2026-08-17 15:00:00',

                    'title' =>
                    'Movimentação recente',
                ],
            )
            ->assertCreated();

        $this
            ->createMovement(
                $token,
                [
                    'occurred_at' =>
                    '2026-08-16 12:00:00',

                    'title' =>
                    'Movimentação intermediária',
                ],
            )
            ->assertCreated();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->getJson(
                $this->movementsUrl()
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                3
            )
            ->assertJsonPath(
                '0.title',
                'Movimentação recente',
            )
            ->assertJsonPath(
                '1.title',
                'Movimentação intermediária',
            )
            ->assertJsonPath(
                '2.title',
                'Movimentação antiga',
            );
    }

    public function test_listagem_retorna_contrato_minimo_da_movimentacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $movement =
            $this
            ->createMovement(
                $token,
                [
                    'occurred_at' =>
                    '2026-08-17 16:00:00',

                    'title' =>
                    'Despacho publicado',

                    'description' =>
                    'Despacho disponibilizado no diário eletrônico.',
                ],
            )
            ->assertCreated();

        $movementId =
            $movement->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->getJson(
                $this->movementsUrl()
            )
            ->assertOk()
            ->assertJsonPath(
                '0.id',
                $movementId,
            )
            ->assertJsonPath(
                '0.folder_id',
                $this->folder->id,
            )
            ->assertJsonPath(
                '0.title',
                'Despacho publicado',
            )
            ->assertJsonPath(
                '0.description',
                'Despacho disponibilizado no diário eletrônico.',
            )
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'folder_id',
                    'user_id',
                    'occurred_at',
                    'title',
                    'description',
                    'created_at',
                    'updated_at',

                    'user' => [
                        'id',
                        'name',
                    ],
                ],
            ]);
    }

    public function test_exclui_movimentacao_da_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->createMovement(
                $token,
                [
                    'occurred_at' =>
                    '2026-08-17 16:30:00',

                    'title' =>
                    'Movimentação temporária',
                ],
            );

        $response
            ->assertCreated();

        $movementId =
            $response->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->deleteJson(
                $this->movementUrl(
                    $movementId
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folder_movements',
            [
                'id' =>
                $movementId,
            ],
        );
    }

    public function test_movimentacoes_ficam_isoladas_por_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->createMovement(
                $token,
                [
                    'occurred_at' =>
                    '2026-08-17 17:00:00',

                    'title' =>
                    'Movimentação interna',
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
                "/api/folders/{$otherFolder->id}/movements"
            )
            ->assertNotFound();
    }

    public function test_nao_exclui_movimentacao_de_outra_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->createMovement(
                $token,
                [
                    'occurred_at' =>
                    '2026-08-17 17:30:00',

                    'title' =>
                    'Movimentação da pasta principal',
                ],
            );

        $response
            ->assertCreated();

        $movementId =
            $response->json(
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
            ->deleteJson(
                "/api/folders/{$otherFolder->id}/movements/{$movementId}"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'folder_movements',
            [
                'id' =>
                $movementId,

                'folder_id' =>
                $this->folder->id,
            ],
        );
    }

    private function createMovement(
        string $token,
        array $payload,
    ) {
        return $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->movementsUrl(),
                $payload,
            );
    }

    private function movementsUrl(): string
    {
        return "/api/folders/{$this->folder->id}/movements";
    }

    private function movementUrl(
        int|string $movementId,
    ): string {
        return "{$this->movementsUrl()}/{$movementId}";
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
