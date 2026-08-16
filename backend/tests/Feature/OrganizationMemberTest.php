<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationMemberTest extends TestCase
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
            ->getJson(
                '/api/organization-members'
            )
            ->assertUnauthorized();
    }

    public function test_index_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                '/api/organization-members'
            )
            ->assertBadRequest();
    }

    public function test_usuario_sem_permissao_nao_pode_listar_membros(): void
    {
        $user =
            User::factory()
            ->create();

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
            ->getJson(
                '/api/organization-members'
            )
            ->assertForbidden();
    }

    public function test_super_admin_pode_listar_membros(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/organization-members'
            )
            ->assertOk()
            ->assertJsonFragment([
                'email' =>
                'super-admin@legalis.local',

                'status' =>
                'active',

                'role' =>
                'super-admin',
            ]);
    }

    public function test_index_retorna_apenas_membros_da_organizacao_atual(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $otherUser =
            User::factory()
            ->create([
                'name' =>
                'Usuário outra organização',

                'email' =>
                'outra-organizacao@legalis.local',
            ]);

        $this->attachUser(
            $otherUser,
            $otherOrganization,
        );

        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/organization-members'
            );

        $response
            ->assertOk()
            ->assertJsonMissing([
                'email' =>
                'outra-organizacao@legalis.local',
            ]);
    }

    public function test_index_retorna_status_e_role_contextuais(): void
    {
        $member =
            User::factory()
            ->create([
                'name' =>
                'Membro contextual',

                'email' =>
                'membro-contextual@legalis.local',
            ]);

        $this->attachUser(
            $member,
            $this->organization,
        );

        $role =
            $this->role(
                $this->organization,
                'advogado-junior',
            );

        $this->assignRole(
            $member,
            $this->organization,
            $role,
        );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/organization-members'
            )
            ->assertOk()
            ->assertJsonFragment([
                'id' =>
                $member->id,

                'email' =>
                'membro-contextual@legalis.local',

                'status' =>
                'active',

                'role' =>
                'advogado-junior',
            ]);
    }

    public function test_update_role_exige_autenticacao(): void
    {
        $member =
            $this->createMember();

        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/role",
                [
                    'role' =>
                    'advogado-pleno',
                ],
            )
            ->assertUnauthorized();
    }

    public function test_usuario_sem_permissao_nao_pode_alterar_role(): void
    {
        $actor =
            User::factory()
            ->create();

        $member =
            $this->createMember();

        $this->attachUser(
            $actor,
            $this->organization,
        );

        $token =
            auth('api')->login(
                $actor
            );

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/role",
                [
                    'role' =>
                    'advogado-pleno',
                ],
            )
            ->assertForbidden();
    }

    public function test_super_admin_pode_alterar_role_de_membro(): void
    {
        $member =
            $this->createMember(
                'advogado-junior'
            );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/role",
                [
                    'role' =>
                    'advogado-pleno',
                ],
            )
            ->assertOk()
            ->assertJson([
                'message' =>
                'Função do membro atualizada com sucesso.',
            ]);

        $this->assertUserHasRoleInOrganization(
            $member,
            $this->organization,
            'advogado-pleno',
        );

        $this->assertUserDoesNotHaveRoleInOrganization(
            $member,
            $this->organization,
            'advogado-junior',
        );
    }

    public function test_role_e_obrigatoria(): void
    {
        $member =
            $this->createMember();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/role",
                [],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);
    }

    public function test_role_deve_existir_na_organizacao_atual(): void
    {
        $member =
            $this->createMember();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/role",
                [
                    'role' =>
                    'role-inexistente',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);
    }

    public function test_nao_altera_role_de_usuario_que_nao_pertence_ao_tenant(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $member =
            User::factory()
            ->create();

        $this->attachUser(
            $member,
            $otherOrganization,
        );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/role",
                [
                    'role' =>
                    'advogado-pleno',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'member',
            ]);
    }

    public function test_role_e_isolada_por_organizacao(): void
    {
        $member =
            $this->createMember(
                'advogado-junior'
            );

        $otherOrganization =
            $this->createOrganizationWithRoles();

        $this->attachUser(
            $member,
            $otherOrganization,
        );

        $this->assignRole(
            $member,
            $otherOrganization,
            $this->role(
                $otherOrganization,
                'advogado-senior',
            ),
        );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/role",
                [
                    'role' =>
                    'advogado-pleno',
                ],
            )
            ->assertOk();

        $this->assertUserHasRoleInOrganization(
            $member,
            $this->organization,
            'advogado-pleno',
        );

        $this->assertUserHasRoleInOrganization(
            $member,
            $otherOrganization,
            'advogado-senior',
        );
    }

    public function test_nao_rebaixa_ultimo_administrador_ativo(): void
    {
        $superAdmin =
            $this->superAdmin();

        $this->deactivateOtherAdministrators(
            $superAdmin
        );

        $token =
            auth('api')->login(
                $superAdmin
            );

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$superAdmin->id}/role",
                [
                    'role' =>
                    'advogado-senior',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);

        $this->assertUserHasRoleInOrganization(
            $superAdmin,
            $this->organization,
            'super-admin',
        );
    }

    public function test_pode_rebaixar_administrador_quando_existe_outro_ativo(): void
    {
        $administrator =
            $this->createMember(
                'socio-administrador'
            );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$administrator->id}/role",
                [
                    'role' =>
                    'advogado-senior',
                ],
            )
            ->assertOk();

        $this->assertUserHasRoleInOrganization(
            $administrator,
            $this->organization,
            'advogado-senior',
        );
    }

    public function test_update_status_exige_autenticacao(): void
    {
        $member =
            $this->createMember();

        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/status",
                [
                    'status' =>
                    'inactive',
                ],
            )
            ->assertUnauthorized();
    }

    public function test_usuario_sem_permissao_nao_pode_alterar_status(): void
    {
        $actor =
            User::factory()
            ->create();

        $member =
            $this->createMember();

        $this->attachUser(
            $actor,
            $this->organization,
        );

        $token =
            auth('api')->login(
                $actor
            );

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/status",
                [
                    'status' =>
                    'inactive',
                ],
            )
            ->assertForbidden();
    }

    public function test_super_admin_pode_desativar_membro(): void
    {
        $member =
            $this->createMember(
                'advogado-junior'
            );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/status",
                [
                    'status' =>
                    'inactive',
                ],
            )
            ->assertOk()
            ->assertJson([
                'message' =>
                'Status do membro atualizado com sucesso.',
            ]);

        $this->assertDatabaseHas(
            'organization_user',
            [
                'organization_id' =>
                $this->organization->id,

                'user_id' =>
                $member->id,

                'status' =>
                'inactive',
            ],
        );
    }

    public function test_super_admin_pode_reativar_membro(): void
    {
        $member =
            $this->createMember(
                'advogado-junior'
            );

        $this->organization
            ->users()
            ->updateExistingPivot(
                $member->id,
                [
                    'status' =>
                    'inactive',
                ],
            );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/status",
                [
                    'status' =>
                    'active',
                ],
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'organization_user',
            [
                'organization_id' =>
                $this->organization->id,

                'user_id' =>
                $member->id,

                'status' =>
                'active',
            ],
        );
    }

    public function test_status_e_obrigatorio(): void
    {
        $member =
            $this->createMember();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/status",
                [],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);
    }

    public function test_status_deve_ser_valido(): void
    {
        $member =
            $this->createMember();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/status",
                [
                    'status' =>
                    'blocked',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);
    }

    public function test_nao_altera_status_de_usuario_que_nao_pertence_ao_tenant(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $member =
            User::factory()
            ->create();

        $this->attachUser(
            $member,
            $otherOrganization,
        );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$member->id}/status",
                [
                    'status' =>
                    'inactive',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'member',
            ]);
    }

    public function test_nao_desativa_ultimo_administrador_ativo(): void
    {
        $superAdmin =
            $this->superAdmin();

        $this->deactivateOtherAdministrators(
            $superAdmin
        );

        $token =
            auth('api')->login(
                $superAdmin
            );

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$superAdmin->id}/status",
                [
                    'status' =>
                    'inactive',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        $this->assertDatabaseHas(
            'organization_user',
            [
                'organization_id' =>
                $this->organization->id,

                'user_id' =>
                $superAdmin->id,

                'status' =>
                'active',
            ],
        );
    }

    public function test_pode_desativar_administrador_quando_existe_outro_ativo(): void
    {
        $administrator =
            $this->createMember(
                'socio-administrador'
            );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/organization-members/{$administrator->id}/status",
                [
                    'status' =>
                    'inactive',
                ],
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'organization_user',
            [
                'organization_id' =>
                $this->organization->id,

                'user_id' =>
                $administrator->id,

                'status' =>
                'inactive',
            ],
        );
    }

    private function createMember(
        string $roleName = 'advogado-junior',
    ): User {
        $user =
            User::factory()
            ->create();

        $this->attachUser(
            $user,
            $this->organization,
        );

        $this->assignRole(
            $user,
            $this->organization,
            $this->role(
                $this->organization,
                $roleName,
            ),
        );

        return $user;
    }

    private function createOrganizationWithRoles(): Organization
    {
        $organization =
            Organization::factory()
            ->create();

        $sourceRoles =
            Role::query()
            ->where(
                'organization_id',
                $this->organization->id,
            )
            ->where(
                'guard_name',
                'api',
            )
            ->with('permissions')
            ->get();

        foreach ($sourceRoles as $sourceRole) {
            $role =
                Role::query()
                ->create([
                    'organization_id' =>
                    $organization->id,

                    'name' =>
                    $sourceRole->name,

                    'guard_name' =>
                    $sourceRole->guard_name,

                    'description' =>
                    $sourceRole->description,
                ]);

            $role->syncPermissions(
                $sourceRole->permissions
            );
        }

        return $organization;
    }

    private function role(
        Organization $organization,
        string $name,
    ): Role {
        return Role::query()
            ->where(
                'organization_id',
                $organization->id,
            )
            ->where(
                'name',
                $name,
            )
            ->where(
                'guard_name',
                'api',
            )
            ->firstOrFail();
    }

    private function assignRole(
        User $user,
        Organization $organization,
        Role $role,
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

            $user->syncRoles([
                $role,
            ]);
        } finally {
            setPermissionsTeamId(
                $previousTeamId
            );

            $this->clearPermissionRelations(
                $user
            );
        }
    }

    private function assertUserHasRoleInOrganization(
        User $user,
        Organization $organization,
        string $roleName,
    ): void {
        $this->assertDatabaseHas(
            'model_has_roles',
            [
                'organization_id' =>
                $organization->id,

                'model_type' =>
                User::class,

                'model_id' =>
                $user->id,

                'role_id' =>
                $this
                    ->role(
                        $organization,
                        $roleName,
                    )
                    ->id,
            ],
        );
    }

    private function assertUserDoesNotHaveRoleInOrganization(
        User $user,
        Organization $organization,
        string $roleName,
    ): void {
        $this->assertDatabaseMissing(
            'model_has_roles',
            [
                'organization_id' =>
                $organization->id,

                'model_type' =>
                User::class,

                'model_id' =>
                $user->id,

                'role_id' =>
                $this
                    ->role(
                        $organization,
                        $roleName,
                    )
                    ->id,
            ],
        );
    }

    private function deactivateOtherAdministrators(
        User $except,
    ): void {
        $administratorIds =
            User::query()
            ->whereIn(
                'email',
                [
                    'super-admin@legalis.local',
                    'socio-administrador@legalis.local',
                ],
            )
            ->whereKeyNot(
                $except->id
            )
            ->pluck(
                'id'
            );

        foreach ($administratorIds as $userId) {
            $this->organization
                ->users()
                ->updateExistingPivot(
                    $userId,
                    [
                        'status' =>
                        'inactive',
                    ],
                );
        }
    }

    private function superAdmin(): User
    {
        return User::query()
            ->where(
                'email',
                'super-admin@legalis.local',
            )
            ->firstOrFail();
    }

    private function loginAsSuperAdmin(): string
    {
        return auth('api')->login(
            $this->superAdmin()
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

                    'joined_at' =>
                    now(),
                ],
            ]);
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
