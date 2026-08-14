<?php

namespace Database\Seeders;

use App\Models\Qualification;
use Illuminate\Database\Seeder;

class QualificationSeeder extends Seeder
{
    public function run(): void
    {
        $qualifications = [
            'Autor',
            'Réu',
            'Requerente',
            'Requerido',
            'Exequente',
            'Executado',
            'Recorrente',
            'Recorrido',
            'Interessado',
        ];

        foreach ($qualifications as $name) {
            Qualification::query()->firstOrCreate([
                'name' => $name,
            ]);
        }
    }
}