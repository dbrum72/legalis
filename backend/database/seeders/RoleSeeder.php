<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();

        $roles = [
            'super-admin' => [
                'description' =>
                'Acesso total ao escritório',
            ],

            'socio-administrador' => [
                'description' =>
                'Gestão administrativa e jurídica completa do escritório',
            ],

            'socio' => [
                'description' =>
                'Gestão jurídica e acompanhamento geral do escritório',
            ],

            'advogado-senior' => [
                'description' =>
                'Atuação jurídica sênior com acesso operacional amplo',
            ],

            'advogado-pleno' => [
                'description' =>
                'Atuação jurídica plena em clientes, documentos e tarefas',
            ],

            'advogado-junior' => [
                'description' =>
                'Atuação jurídica júnior sob supervisão',
            ],

            'advogado-associado' => [
                'description' =>
                'Atuação jurídica associada em clientes e processos internos',
            ],

            'assistente-juridico' => [
                'description' =>
                'Suporte às atividades jurídicas e administrativas',
            ],

            'estagiario-direito' => [
                'description' =>
                'Apoio jurídico supervisionado com acesso restrito',
            ],

            'paralegal' => [
                'description' =>
                'Suporte operacional especializado às atividades jurídicas',
            ],
        ];

        Organization::query()
            ->orderBy('id')
            ->each(
                function (
                    Organization $organization
                ) use ($roles): void {
                    foreach (
                        $roles
                        as $name => $data
                    ) {
                        Role::query()
                            ->updateOrCreate(
                                [
                                    'organization_id' =>
                                    $organization->id,

                                    'name' =>
                                    $name,

                                    'guard_name' =>
                                    'api',
                                ],
                                [
                                    'description' =>
                                    $data['description'],
                                ],
                            );
                    }
                }
            );

        app(
            PermissionRegistrar::class
        )->forgetCachedPermissions();
    }
}
