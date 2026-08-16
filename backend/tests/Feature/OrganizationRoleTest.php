<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                    'status' =>
                    'active',

                    'joined_at' =>
                    now(),
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
                ],
            ])
            ->assertJsonFragment([
                'name' =>
                'super-admin',
            ])
            ->assertJsonFragment([
                'name' =>
                'advogado-junior',
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
                'organization_id' =>
                $otherOrganization->getKey(),

                'name' =>
                'role-exclusiva-outra-organizacao',

                'guard_name' =>
                'api',

                'description' =>
                'Role exclusiva para teste',
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
            $response->json()
            as $role
        ) {
            $this->assertSame(
                [
                    'id',
                    'name',
                ],
                array_keys(
                    $role
                )
            );
        }
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
