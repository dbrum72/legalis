<?php

namespace App\Support\Organizations;

final class OrganizationRoleDefinitions
{
    public const SUPER_ADMIN =
        'super-admin';

    public const SOCIO_ADMINISTRADOR =
        'socio-administrador';

    public const SOCIO =
        'socio';

    public const ADVOGADO_SENIOR =
        'advogado-senior';

    public const ADVOGADO_PLENO =
        'advogado-pleno';

    public const ADVOGADO_JUNIOR =
        'advogado-junior';

    public const ADVOGADO_ASSOCIADO =
        'advogado-associado';

    public const ASSISTENTE_JURIDICO =
        'assistente-juridico';

    public const ESTAGIARIO_DIREITO =
        'estagiario-direito';

    public const PARALEGAL =
        'paralegal';

    public static function definitions(): array
    {
        $basePermissions = [
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',

            'files.view',
            'files.upload',
            'files.delete',

            'folders.view',
            'folders.create',
            'folders.update',
            'folders.delete',

            'publications.view',
            'publications.review',

            'documents.generate',

            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',

            'users.view',

            'roles.view',
            'roles.update',
        ];

        $organizationAdministrationPermissions = [
            'organization-members.view',
            'organization-members.invite',
            'organization-members.update-role',
            'organization-members.update-status',

            'publications.manage-monitoring',
            'publications.sync',
        ];

        $allPermissions =
            array_values(
                array_unique(
                    array_merge(
                        $basePermissions,
                        $organizationAdministrationPermissions,
                    )
                )
            );

        return [
            self::SUPER_ADMIN => [
                'description' => 'Acesso total ao escritório',

                'permissions' => $allPermissions,
            ],

            self::SOCIO_ADMINISTRADOR => [
                'description' => 'Gestão administrativa e jurídica completa do escritório',

                'permissions' => $allPermissions,
            ],

            self::SOCIO => [
                'description' => 'Gestão jurídica e acompanhamento geral do escritório',

                'permissions' => array_values(
                    array_diff(
                        $basePermissions,
                        [
                            'roles.update',
                        ],
                    )
                ),
            ],

            self::ADVOGADO_SENIOR => [
                'description' => 'Atuação jurídica sênior com acesso operacional amplo',

                'permissions' => array_values(
                    array_diff(
                        $basePermissions,
                        [
                            'roles.view',
                            'roles.update',
                        ],
                    )
                ),
            ],

            self::ADVOGADO_PLENO => [
                'description' => 'Atuação jurídica plena em clientes, documentos e tarefas',

                'permissions' => [
                    'clients.view',
                    'clients.create',
                    'clients.update',

                    'files.view',
                    'files.upload',

                    'folders.view',
                    'folders.create',
                    'folders.update',

                    'publications.view',
                    'publications.review',

                    'documents.generate',

                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                ],
            ],

            self::ADVOGADO_JUNIOR => [
                'description' => 'Atuação jurídica júnior sob supervisão',

                'permissions' => [
                    'clients.view',
                    'clients.create',
                    'clients.update',

                    'files.view',
                    'files.upload',

                    'folders.view',

                    'publications.view',
                    'publications.review',

                    'documents.generate',

                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                ],
            ],

            self::ADVOGADO_ASSOCIADO => [
                'description' => 'Atuação jurídica associada em clientes e processos internos',

                'permissions' => [
                    'clients.view',
                    'clients.create',
                    'clients.update',

                    'files.view',
                    'files.upload',

                    'folders.view',
                    'folders.update',

                    'publications.view',
                    'publications.review',

                    'documents.generate',

                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                ],
            ],

            self::ASSISTENTE_JURIDICO => [
                'description' => 'Suporte às atividades jurídicas e administrativas',

                'permissions' => [
                    'clients.view',

                    'files.view',
                    'files.upload',

                    'folders.view',

                    'publications.view',
                    'publications.review',

                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                ],
            ],

            self::ESTAGIARIO_DIREITO => [
                'description' => 'Apoio jurídico supervisionado com acesso restrito',

                'permissions' => [
                    'clients.view',

                    'files.view',

                    'folders.view',

                    'publications.view',
                    'publications.review',

                    'documents.generate',

                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                ],
            ],

            self::PARALEGAL => [
                'description' => 'Suporte operacional especializado às atividades jurídicas',

                'permissions' => [
                    'clients.view',
                    'clients.create',
                    'clients.update',

                    'files.view',
                    'files.upload',

                    'folders.view',
                    'folders.update',

                    'publications.view',
                    'publications.review',

                    'tasks.view',
                    'tasks.create',
                    'tasks.update',
                ],
            ],
        ];
    }

    public static function names(): array
    {
        return array_keys(
            self::definitions()
        );
    }

    public static function permissions(): array
    {
        $permissions = [];

        foreach (
            self::definitions() as $definition
        ) {
            $permissions =
                array_merge(
                    $permissions,
                    $definition['permissions'],
                );
        }

        return array_values(
            array_unique(
                $permissions
            )
        );
    }
}
