<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        $roles = [
            [
                'name' => 'super-admin',
                'guard_name' => 'api',
                'description' => 'Acesso total ao sistema',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'socio-administrador',
                'guard_name' => 'api',
                'description' => 'Gestão administrativa e jurídica completa do escritório',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'socio',
                'guard_name' => 'api',
                'description' => 'Gestão jurídica e acompanhamento geral do escritório',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'advogado-senior',
                'guard_name' => 'api',
                'description' => 'Atuação jurídica sênior com acesso operacional amplo',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'advogado-pleno',
                'guard_name' => 'api',
                'description' => 'Atuação jurídica plena em clientes, documentos e tarefas',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'advogado-junior',
                'guard_name' => 'api',
                'description' => 'Atuação jurídica júnior sob supervisão',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'advogado-associado',
                'guard_name' => 'api',
                'description' => 'Atuação jurídica associada em clientes e processos internos',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'assistente-juridico',
                'guard_name' => 'api',
                'description' => 'Suporte às atividades jurídicas e administrativas',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'estagiario-direito',
                'guard_name' => 'api',
                'description' => 'Apoio jurídico supervisionado com acesso restrito',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'paralegal',
                'guard_name' => 'api',
                'description' => 'Suporte operacional especializado às atividades jurídicas',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        DB::table('roles')->upsert(
            $roles,
            ['name', 'guard_name'],
            ['description', 'updated_at']
        );
    }
}
