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

    public const SECONDARY_NAME =
    'Escritório Advocacia & Associados';

    public const SECONDARY_SLUG =
    'advocacia-associados';

    public function run(): void
    {
        $organizations = [
            [
                'name' => self::DEFAULT_NAME,
                'slug' => self::DEFAULT_SLUG,
                'status' => 'active',
            ],
            [
                'name' => self::SECONDARY_NAME,
                'slug' => self::SECONDARY_SLUG,
                'status' => 'active',
            ],
        ];

        foreach ($organizations as $organization) {
            Organization::updateOrCreate(
                [
                    'slug' => $organization['slug'],
                ],
                [
                    'name' => $organization['name'],
                    'status' => $organization['status'],
                ],
            );
        }
    }
}
