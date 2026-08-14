<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' =>
            Organization::factory(),

            'name' =>
            fake()->name(),

            'document' =>
            fake()->unique()->numerify(
                '###########',
            ),

            'identity_document' =>
            fake()->optional()->numerify(
                '#########',
            ),

            'identity_issuer' =>
            fake()->optional()->lexify(
                '???',
            ),

            'marital_status_id' =>
            null,

            'profession' =>
            fake()->optional()->jobTitle(),

            'address' =>
            fake()->optional()->streetAddress(),

            'address_complement' =>
            null,

            'district' =>
            null,

            'city' =>
            fake()->optional()->city(),

            'postal_code' =>
            fake()->optional()->numerify(
                '########',
            ),

            'phone' =>
            fake()->optional()->numerify(
                '###########',
            ),

            'whatsapp' =>
            false,

            'email' =>
            fake()->optional()->safeEmail(),
        ];
    }
}
