<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public const DEFAULT_NAME =
        'Escritório Legalis';

    public const DEFAULT_SLUG =
        'escritorio-legalis';

    public function run(): void
    {
        Organization::updateOrCreate(
            [
                'slug' => self::DEFAULT_SLUG,
            ],
            [
                'name' => self::DEFAULT_NAME,
                'status' => 'active',
            ],
        );
    }
}