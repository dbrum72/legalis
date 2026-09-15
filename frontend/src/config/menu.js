export default [
    {
        id: 'dashboard',
        label: 'Dashboard',
        name: 'dashboard',
        icon: 'dashboard',
    },

    {
        id: 'agenda',
        label: 'Agenda',
        name: 'agenda',
        icon: 'calendar',
    },

    {
        id: 'clients',
        label: 'Clientes',
        name: 'clients',
        icon: 'user',
        permission: 'clients.view',
    },

    {
        id: 'folders',
        label: 'Pastas',
        name: 'folders',
        icon: 'file',
        permission: 'folders.view',
    },

    {
        id: 'organization-members',
        label: 'Equipe',
        name: 'organization-members',
        icon: 'users',
        permission: 'organization-members.view',
    },

    {
        id: 'publications',
        label: 'Publicações',
        name: 'publications',
        icon: 'file',
        permission: 'publications.view',
    },

    {
        id: 'finance',
        label: 'Financeiro',
        name: 'finance',
        icon: 'wallet',
        permission: 'finance.view',
    },

    {
        id: 'settings',
        label: 'Configurações',
        name: 'settings',
        icon: 'settings',
        permissionsAny: ['roles.view', 'finance.view', 'expenses.view', 'documents.generate'],
    },
]
