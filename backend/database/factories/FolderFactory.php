<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Folder>
 */
class FolderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' =>
            Organization::factory(),

            'name' =>
            fake()->sentence(3),

            'process_number' =>
            null,
        ];
    }
}
