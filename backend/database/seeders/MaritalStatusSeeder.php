<?php

namespace Database\Seeders;

use App\Models\MaritalStatus;
use Illuminate\Database\Seeder;

class MaritalStatusSeeder extends Seeder
{
    public function run(): void
    {
        $maritalStatuses = [
            'solteiro(a)',
            'casado(a)',
            'divorciado(a)',
            'união estável',
            'viúvo(a)',
        ];

        foreach ($maritalStatuses as $name) {
            MaritalStatus::firstOrCreate([
                'name' => $name,
            ]);
        }
    }
}