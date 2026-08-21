<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Support\Organizations\OrganizationRoleDefinitions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $registrar =
            app(
                PermissionRegistrar::class
            );

        $registrar
            ->forgetCachedPermissions();

        $definitions =
            OrganizationRoleDefinitions::definitions();

        Organization::query()
            ->orderBy('id')
            ->each(
                function (
                    Organization $organization
                ) use (
                    $definitions,
                ): void {
                    foreach (
                        $definitions
                        as $name => $definition
                    ) {
                        Role::query()
                            ->updateOrCreate(
                                [
                                    'organization_id' =>
                                    $organization->id,

                                    'name' =>
                                    $name,

                                    'guard_name' =>
                                    'api',
                                ],
                                [
                                    'description' =>
                                    $definition['description'],
                                ],
                            );
                    }
                }
            );

        $registrar
            ->forgetCachedPermissions();
    }
}
