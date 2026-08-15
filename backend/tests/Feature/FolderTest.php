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
