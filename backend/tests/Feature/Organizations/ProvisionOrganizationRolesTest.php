<?php

namespace Tests\Feature\Organizations;

use App\Models\Organization;
use App\Services\Organizations\ProvisionOrganizationRoles;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProvisionOrganizationRolesTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );

        $this->organization =
            Organization::factory()
            ->create();
    }

    public function test_provisiona_todas_as_roles_da_organizacao(): void
    {
        $service =
            app(
                ProvisionOrganizationRoles::class
            );

        $service->execute(
            $this->organization
        );

        $roles =
            Role::query()
            ->where(
                'organization_id',
                $this->organization->id,
            )
            ->where(
                'guard_name',
                'api',
            )
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            [
                'advogado-associado',
                'advogado-junior',
                'advogado-pleno',
                'advogado-senior',
                'assistente-juridico',
                'estagiario-direito',
                'paralegal',
                'socio',
                'socio-administrador',
                'super-admin',
            ],
            $roles,
        );
    }

    public function test_socio_administrador_recebe_todas_as_permissoes_administrativas(): void
    {
        $service =
            app(
                ProvisionOrganizationRoles::class
            );

        $service->execute(
            $this->organization
        );

        $role =
            $this->role(
                'socio-administrador'
            );

        $permissions =
            $role
            ->permissions
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            [
                'clients.create',
                'clients.delete',
                'clients.update',
                'clients.view',
                'documents.generate',
                'files.delete',
                'files.upload',
                'files.view',
                'folders.create',
                'folders.delete',
                'folders.update',
                'folders.view',
                'organization-members.invite',
                'organization-members.update-role',
                'organization-members.update-status',
                'organization-members.view',
                'roles.update',
                'roles.view',
                'tasks.create',
                'tasks.delete',
                'tasks.update',
                'tasks.view',
                'users.view',
            ],
            $permissions,
        );
    }

    public function test_roles_sao_isoladas_por_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $service =
            app(
                ProvisionOrganizationRoles::class
            );

        $service->execute(
            $this->organization
        );

        $this->assertSame(
            10,
            Role::query()
                ->where(
                    'organization_id',
                    $this->organization->id,
                )
                ->count(),
        );

        $this->assertSame(
            0,
            Role::query()
                ->where(
                    'organization_id',
                    $otherOrganization->id,
                )
                ->count(),
        );
    }

    public function test_provisionamento_e_idempotente(): void
    {
        $service =
            app(
                ProvisionOrganizationRoles::class
            );

        $service->execute(
            $this->organization
        );

        $service->execute(
            $this->organization
        );

        $this->assertSame(
            10,
            Role::query()
                ->where(
                    'organization_id',
                    $this->organization->id,
                )
                ->where(
                    'guard_name',
                    'api',
                )
                ->count(),
        );
    }

    private function role(
        string $name,
    ): Role {
        return Role::query()
            ->where(
                'organization_id',
                $this->organization->id,
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
}
