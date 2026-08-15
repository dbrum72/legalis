<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(
            PermissionRegistrar::class
        );

        $registrar->forgetCachedPermissions();

        $previousTeamId =
            getPermissionsTeamId();

        $allPermissions = [
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',

            'files.view',
            'files.upload',
            'files.delete',

            'folders.view',
            'folders.create',
            'folders.update',
            'folders.delete',

            'documents.generate',

            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',

            'users.view',

            'roles.view',
            'roles.update',
        ];

        $roles = [
            'super-admin' =>
            $allPermissions,

            'socio-administrador' =>
            $allPermissions,

            'socio' =>
            array_values(
                array_diff(
                    $allPermissions,
                    [
                        'roles.update',
                    ],
                )
            ),

            'advogado-senior' =>
            array_values(
                array_diff(
                    $allPermissions,
                    [
                        'roles.view',
                        'roles.update',
                    ],
                )
            ),

            'advogado-pleno' => [
                'clients.view',
                'clients.create',
                'clients.update',

                'files.view',
                'files.upload',

                'folders.view',
                'folders.create',
                'folders.update',

                'documents.generate',

                'tasks.view',
                'tasks.create',
                'tasks.update',
            ],

            'advogado-junior' => [
                'clients.view',
                'clients.create',
                'clients.update',

                'files.view',
                'files.upload',

                'folders.view',

                'documents.generate',

                'tasks.view',
                'tasks.create',
                'tasks.update',
            ],

            'advogado-associado' => [
                'clients.view',
                'clients.create',
                'clients.update',

                'files.view',
                'files.upload',

                'folders.view',
                'folders.update',

                'documents.generate',

                'tasks.view',
                'tasks.create',
                'tasks.update',
            ],

            'estagiario-direito' => [
                'clients.view',

                'files.view',

                'folders.view',

                'documents.generate',

                'tasks.view',
                'tasks.create',
                'tasks.update',
            ],

            'paralegal' => [
                'clients.view',
                'clients.create',
                'clients.update',

                'files.view',
                'files.upload',

                'folders.view',
                'folders.update',

                'tasks.view',
                'tasks.create',
                'tasks.update',
            ],

            'assistente-juridico' => [
                'clients.view',

                'files.view',
                'files.upload',

                'folders.view',

                'tasks.view',
                'tasks.create',
                'tasks.update',
            ],
        ];

        try {
            Organization::query()
                ->orderBy('id')
                ->each(
                    function (
                        Organization $organization
                    ) use ($roles): void {
                        setPermissionsTeamId(
                            $organization->id
                        );

                        foreach (
                            $roles
                            as $name => $rolePermissions
                        ) {
                            $role = Role::query()
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
                                $rolePermissions
                            );

                            $user = $organization
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

                            if ($user === null) {
                                continue;
                            }

                            $this->clearUserPermissionRelations(
                                $user
                            );

                            $user->syncRoles([
                                $role,
                            ]);

                            $this->clearUserPermissionRelations(
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
