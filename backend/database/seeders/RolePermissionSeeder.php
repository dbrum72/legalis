<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Support\Organizations\OrganizationRoleDefinitions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar =
            app(
                PermissionRegistrar::class
            );

        $registrar
            ->forgetCachedPermissions();

        $previousTeamId =
            getPermissionsTeamId();

        $definitions =
            OrganizationRoleDefinitions::definitions();

        try {
            Organization::query()
                ->orderBy('id')
                ->each(
                    function (
                        Organization $organization
                    ) use (
                        $definitions,
                    ): void {
                        setPermissionsTeamId(
                            $organization->id
                        );

                        foreach (
                            $definitions
                            as $name => $definition
                        ) {
                            $role =
                                Role::query()
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

                            $role->syncPermissions(
                                $definition['permissions']
                            );

                            $user =
                                $organization
                                ->users()
                                ->where(
                                    'users.email',
                                    $name
                                        . '@legalis.local',
                                )
                                ->wherePivot(
                                    'status',
                                    'active',
                                )
                                ->first();

                            if (
                                $user === null
                            ) {
                                continue;
                            }

                            $this
                                ->clearUserPermissionRelations(
                                    $user
                                );

                            $user->syncRoles([
                                $role,
                            ]);

                            $this
                                ->clearUserPermissionRelations(
                                    $user
                                );
                        }
                    }
                );
        } finally {
            setPermissionsTeamId(
                $previousTeamId
            );

            $registrar
                ->forgetCachedPermissions();
        }
    }

    private function clearUserPermissionRelations(
        User $user,
    ): void {
        $user->unsetRelation(
            'roles'
        );

        $user->unsetRelation(
            'permissions'
        );
    }
}
