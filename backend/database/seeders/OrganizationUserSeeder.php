<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationUserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where(
                'slug',
                OrganizationSeeder::DEFAULT_SLUG,
            )
            ->firstOrFail();

        $userIds = User::query()
            ->pluck('id')
            ->all();

        if ($userIds === []) {
            return;
        }

        $organization
            ->users()
            ->syncWithPivotValues(
                $userIds,
                [
                    'status' => 'active',
                ],
                false,
            );
    }
}