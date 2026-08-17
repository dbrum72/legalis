export default [
    {
        id: 'dashboard',
        label: 'Dashboard',
        name: 'dashboard',
        icon: 'dashboard',
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
]
