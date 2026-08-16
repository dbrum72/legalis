<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        $permissions = [
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

            'organization-members.view',
            'organization-members.invite',
            'organization-members.update-role',
            'organization-members.update-status',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate(
                $permission,
                'api'
            );
        }

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }
}
