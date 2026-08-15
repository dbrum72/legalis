<?php

namespace Tests\Feature\Tenancy;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organizationA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            DatabaseSeeder::class
        );

        $this->organizationA =
            Organization::query()
            ->where(
                'slug',
                OrganizationSeeder::DEFAULT_SLUG,
            )
            ->firstOrFail();
    }

    public function test_mesmo_usuario_pode_ter_roles_diferentes_por_organizacao(): void
    {
        $user =
            $this->superAdmin();

        $organizationB =
            $this->createSecondOrganization(
                $user
            );

        $this->assignRole(
            $user,
            $organizationB,
            'assistente-juridico',
        );

        $responseA =
            $this->context(
                $user,
                $this->organizationA,
            );

        $responseB =
            $this->context(
                $user,
                $organizationB,
            );

        $this->assertContains(
            'super-admin',
            $responseA->json(
                'roles'
            ),
        );

        $this->assertNotContains(
            'assistente-juridico',
            $responseA->json(
                'roles'
            ),
        );

        $this->assertContains(
            'assistente-juridico',
            $responseB->json(
                'roles'
            ),
        );

        $this->assertNotContains(
            'super-admin',
            $responseB->json(
                'roles'
            ),
        );
    }

    public function test_permissions_mudam_ao_trocar_x_tenant(): void
    {
        $user =
            $this->superAdmin();

        $organizationB =
            $this->createSecondOrganization(
                $user
            );

        $this->assignRole(
            $user,
            $organizationB,
            'assistente-juridico',
        );

        $responseA =
            $this->context(
                $user,
                $this->organizationA,
            );

        $responseB =
            $this->context(
                $user,
                $organizationB,
            );

        $this->assertContains(
            'folders.delete',
            $responseA->json(
                'permissions'
            ),
        );

        $this->assertNotContains(
            'folders.delete',
            $responseB->json(
                'permissions'
            ),
        );

        $this->assertContains(
            'folders.view',
            $responseB->json(
                'permissions'
            ),
        );
    }

    public function test_can_permite_acao_na_organizacao_com_permission(): void
    {
        $user =
            $this->superAdmin();

        $folder =
            Folder::factory()
            ->for(
                $this->organizationA
            )
            ->create();

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $this
                    ->organizationA
                    ->slug,
            )
            ->deleteJson(
                "/api/folders/{$folder->id}"
            )
            ->assertNoContent();
    }

    public function test_can_bloqueia_acao_na_organizacao_sem_permission(): void
    {
        $user =
            $this->superAdmin();

        $organizationB =
            $this->createSecondOrganization(
                $user
            );

        $this->assignRole(
            $user,
            $organizationB,
            'assistente-juridico',
        );

        $folder =
            Folder::factory()
            ->for(
                $organizationB
            )
            ->create();

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organizationB->slug,
            )
            ->deleteJson(
                "/api/folders/{$folder->id}"
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'folders',
            [
                'id' =>
                $folder->id,
            ],
        );
    }

    private function createSecondOrganization(
        User $user,
    ): Organization {
        $organization =
            Organization::factory()
            ->create();

        $organization
            ->users()
            ->attach(
                $user->id,
                [
                    'status' =>
                    'active',
                ],
            );

        $this->provisionRoles(
            $organization
        );

        return $organization;
    }

    private function provisionRoles(
        Organization $organization,
    ): void {
        $templates =
            Role::query()
            ->where(
                'organization_id',
                $this
                    ->organizationA
                    ->id,
            )
            ->with('permissions')
            ->get();

        foreach (
            $templates as $template
        ) {
            $role =
                Role::query()
                ->create([
                    'organization_id' =>
                    $organization->id,

                    'name' =>
                    $template->name,

                    'guard_name' =>
                    $template
                        ->guard_name,

                    'description' =>
                    $template
                        ->description,
                ]);

            $role
                ->syncPermissions(
                    $template
                        ->permissions
                );
        }
    }

    private function assignRole(
        User $user,
        Organization $organization,
        string $roleName,
    ): void {
        $previousTeamId =
            getPermissionsTeamId();

        try {
            setPermissionsTeamId(
                $organization->id
            );

            $user
                ->unsetRelation('roles')
                ->unsetRelation(
                    'permissions'
                );

            $role =
                Role::query()
                ->where(
                    'organization_id',
                    $organization->id,
                )
                ->where(
                    'name',
                    $roleName,
                )
                ->where(
                    'guard_name',
                    'api',
                )
                ->firstOrFail();

            $user->syncRoles([
                $role,
            ]);
        } finally {
            setPermissionsTeamId(
                $previousTeamId
            );

            $user
                ->unsetRelation('roles')
                ->unsetRelation(
                    'permissions'
                );
        }
    }

    private function context(
        User $user,
        Organization $organization,
    ) {
        $token =
            auth('api')->login(
                $user
            );

        return $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            )
            ->getJson(
                '/api/auth/context'
            )
            ->assertOk();
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
}
