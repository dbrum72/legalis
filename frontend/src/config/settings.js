export const catalogPermissions = ['finance.view', 'expenses.view']
export const settingsPermissions = [...catalogPermissions, 'roles.view', 'documents.generate']

export const settingsEntries = [
    {
        name: 'settings.document-templates',
        title: 'Modelos de documentos',
        group: 'Cadastros de apoio',
        description: 'Importe modelos Word e consulte os campos usados na geração de documentos.',
        permission: 'documents.generate',
    },
    {
        name: 'settings.categories',
        title: 'Categorias financeiras',
        group: 'Cadastros de apoio',
        description: 'Organize as categorias usadas nas contas a pagar e nas despesas.',
        permissionsAny: catalogPermissions,
    },
    {
        name: 'settings.cost-centers',
        title: 'Centros de custo',
        group: 'Cadastros de apoio',
        description: 'Agrupe os custos por área, unidade ou projeto do escritório.',
        permissionsAny: catalogPermissions,
    },
    {
        name: 'role-permissions',
        title: 'Perfis e permissões',
        group: 'Acessos',
        description: 'Defina os acessos concedidos a cada função da equipe.',
        permission: 'roles.view',
    },
]

export function permits(auth, rule) {
    return (
        (!rule.permission || auth.hasPermission(rule.permission)) &&
        (!rule.permissionsAny ||
            rule.permissionsAny.some((permission) => auth.hasPermission(permission)))
    )
}
