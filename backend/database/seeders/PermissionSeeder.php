<?php

namespace Database\Seeders;

use App\Support\Organizations\OrganizationRoleDefinitions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar =
            app(
                PermissionRegistrar::class
            );

        $registrar
            ->forgetCachedPermissions();

        foreach (
            OrganizationRoleDefinitions::permissions()
            as $permission
        ) {
            Permission::findOrCreate(
                $permission,
                'api',
            );
        }

        $registrar
            ->forgetCachedPermissions();
    }
}
