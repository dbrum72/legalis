<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationRoleTest extends TestCase
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
                '/api/organization-roles'
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
                '/api/organization-roles'
            )
            ->assertBadRequest();
    }

    public function test_usuario_sem_permissao_nao_pode_listar_roles(): void
    {
        $user =
            User::factory()
                ->create();

        $this->organization
            ->users()
            ->attach(
                $user->getKey(),
                [
                    'status' => 'active',

                    'joined_at' => now(),
                ],
            );

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->asTenant(
                $token
            )
            ->getJson(
                '/api/organization-roles'
            )
            ->assertForbidden();
    }

    public function test_super_admin_pode_listar_roles(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
                ->asTenant(
                    $token
                )
                ->getJson(
                    '/api/organization-roles'
                );

        $response
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'description',
                    'permissions_count',
                ],
            ])
            ->assertJsonFragment([
                'name' => 'super-admin',
            ])
            ->assertJsonFragment([
                'name' => 'advogado-junior',
            ]);
    }

    public function test_index_retorna_apenas_roles_da_organizacao_atual(): void
    {
        $otherOrganization =
            Organization::factory()
                ->create();

        $exclusiveRole =
            Role::query()
                ->create([
                    'organization_id' => $otherOrganization->getKey(),

                    'name' => 'role-exclusiva-outra-organizacao',

                    'guard_name' => 'api',

                    'description' => 'Role exclusiva para teste',
                ]);

        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
                ->asTenant(
                    $token
                )
                ->getJson(
                    '/api/organization-roles'
                );

        $response->assertOk();

        $returnedIds =
            collect(
                $response->json()
            )
                ->pluck('id');

        $this->assertFalse(
            $returnedIds->contains(
                $exclusiveRole->getKey()
            )
        );
    }

    public function test_roles_sao_retornadas_em_ordem_alfabetica(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
                ->asTenant(
                    $token
                )
                ->getJson(
                    '/api/organization-roles'
                );

        $response->assertOk();

        $names =
            collect(
                $response->json()
            )
                ->pluck('name')
                ->all();

        $sortedNames =
            $names;

        sort(
            $sortedNames,
            SORT_STRING
        );

        $this->assertSame(
            $sortedNames,
            $names
        );
    }

    public function test_retorna_somente_campos_publicos_da_role(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
                ->asTenant(
                    $token
                )
                ->getJson(
                    '/api/organization-roles'
                );

        $response->assertOk();

        foreach (
            $response->json() as $role
        ) {
            $this->assertSame(
                [
                    'id',
                    'name',
                    'description',
                    'permissions_count',
                ],
                array_keys(
                    $role
                )
            );
        }
    }

    public function test_usuario_autorizado_pode_consultar_permissoes_da_role(): void
    {
        $role = $this->organizationRole('advogado-junior');

        $this
            ->asTenant($this->loginAsSuperAdmin())
            ->getJson("/api/organization-roles/{$role->getKey()}")
            ->assertOk()
            ->assertJsonPath('id', $role->getKey())
            ->assertJsonPath('name', 'advogado-junior')
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'permissions',
                'available_permissions',
            ]);
    }

    public function test_usuario_autorizado_pode_atualizar_permissoes_da_role(): void
    {
        $role = $this->organizationRole('advogado-junior');

        $permissions = [
            'clients.view',
            'folders.view',
        ];

        $this
            ->asTenant($this->loginAsSuperAdmin())
            ->patchJson(
                "/api/organization-roles/{$role->getKey()}/permissions",
                compact('permissions'),
            )
            ->assertOk()
            ->assertJsonPath('permissions', $permissions);

        $this->assertEqualsCanonicalizing(
            $permissions,
            $role->refresh()->permissions->pluck('name')->all(),
        );
    }

    public function test_atualizacao_rejeita_permissao_inexistente(): void
    {
        $role = $this->organizationRole('advogado-junior');

        $this
            ->asTenant($this->loginAsSuperAdmin())
            ->patchJson(
                "/api/organization-roles/{$role->getKey()}/permissions",
                ['permissions' => ['permission.inexistente']],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.0');
    }

    public function test_nao_permite_acessar_role_de_outra_organizacao(): void
    {
        $otherOrganization = Organization::factory()->create();

        $role = Role::query()->create([
            'organization_id' => $otherOrganization->getKey(),
            'name' => 'role-externa',
            'guard_name' => 'api',
        ]);

        $this
            ->asTenant($this->loginAsSuperAdmin())
            ->getJson("/api/organization-roles/{$role->getKey()}")
            ->assertNotFound();
    }

    public function test_nao_permite_alterar_permissoes_do_super_admin(): void
    {
        $role = $this->organizationRole('super-admin');

        $this
            ->asTenant($this->loginAsSuperAdmin())
            ->patchJson(
                "/api/organization-roles/{$role->getKey()}/permissions",
                ['permissions' => ['clients.view']],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions');
    }

    public function test_organizacao_deve_manter_role_capaz_de_configurar_permissoes(): void
    {
        $permission = Permission::findByName(
            'roles.update',
            'api',
        );

        $target = $this->organizationRole('socio-administrador');

        setPermissionsTeamId($this->organization->getKey());

        $superAdmin = $this->superAdmin();
        $superAdmin->givePermissionTo($permission);
        $token = auth('api')->login($superAdmin);

        Role::query()
            ->where('organization_id', $this->organization->getKey())
            ->whereKeyNot($target->getKey())
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));

        $remaining = $target->permissions
            ->pluck('name')
            ->reject(fn (string $name) => $name === 'roles.update')
            ->values()
            ->all();

        $this
            ->asTenant($token)
            ->patchJson(
                "/api/organization-roles/{$target->getKey()}/permissions",
                ['permissions' => $remaining],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permissions');
    }

    private function organizationRole(string $name): Role
    {
        return Role::query()
            ->where('organization_id', $this->organization->getKey())
            ->where('guard_name', 'api')
            ->where('name', $name)
            ->firstOrFail();
    }

    private function loginAsSuperAdmin(): string
    {
        return auth('api')->login(
            $this->superAdmin()
        );
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
