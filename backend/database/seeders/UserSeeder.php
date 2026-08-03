<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public const ROLES = [
        'super-admin',
        'socio-administrador',
        'socio',
        'advogado-senior',
        'advogado-pleno',
        'advogado-junior',
        'advogado-associado',
        'assistente-juridico',
        'estagiario-direito',
        'paralegal',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            User::updateOrCreate(
                ['email' => $role.'@legalis.local'],
                [
                    'name' => Str::headline($role),
                    'password' => Hash::make('l3g@l1s'),
                ]
            );
        }
    }
}
