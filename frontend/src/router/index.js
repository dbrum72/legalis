import { createRouter, createWebHistory } from 'vue-router'

import { authGuard } from './guards/auth.js'

import { permissionGuard } from '@/router/guards/permission.js'

import PublicLayout from '@/layouts/PublicLayout.vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),

    routes: [
        {
            path: '/',

            component: PublicLayout,

            children: [
                {
                    path: '',

                    name: 'home',

                    component: () => import('@/views/public/LandingPage.vue'),
                },
            ],
        },

        {
            path: '/login',

            name: 'login',

            component: () => import('@/views/auth/LoginPage.vue'),

            meta: {
                guestOnly: true,
            },
        },

        {
            path: '/register',

            name: 'register',

            component: () => import('@/views/auth/RegisterPage.vue'),

            meta: {
                guestOnly: true,
            },
        },

        {
            path: '/invitations/accept/:token',

            name: 'invitations.accept',

            component: () => import('@/views/auth/InvitationAcceptPage.vue'),
        },

        {
            path: '/organizations/select',

            name: 'organizations.select',

            component: () => import('@/views/auth/OrganizationSelectPage.vue'),

            meta: {
                requiresAuth: true,
            },
        },

        {
            path: '/',

            component: DefaultLayout,

            meta: {
                requiresAuth: true,
                requiresOrganization: true,
            },

            children: [
                {
                    path: 'dashboard',

                    name: 'dashboard',

                    component: () => import('@/views/dashboard/DashboardPage.vue'),

                    meta: {
                        breadcrumb: 'Dashboard',
                    },
                },

                {
                    path: 'agenda',

                    name: 'agenda',

                    component: () => import('@/views/agenda/AgendaPage.vue'),

                    meta: {
                        breadcrumb: 'Agenda',
                    },
                },

                {
                    path: 'playground',

                    name: 'playground',

                    component: () => import('@/views/playground/PlaygroundPage.vue'),
                },

                {
                    path: 'clients',

                    name: 'clients',

                    component: () => import('@/views/clients/ClientListPage.vue'),

                    meta: {
                        breadcrumb: 'Clientes',

                        permission: 'clients.view',
                    },
                },

                {
                    path: 'clients/new',

                    name: 'clients.create',

                    component: () => import('@/views/clients/ClientSavePage.vue'),

                    meta: {
                        breadcrumb: 'Novo cliente',

                        permission: 'clients.create',
                    },
                },

                {
                    path: 'clients/:id',

                    name: 'clients.show',

                    component: () => import('@/views/clients/ClientShowPage.vue'),

                    meta: {
                        breadcrumb: 'Detalhes do cliente',

                        permission: 'clients.view',
                    },
                },

                {
                    path: 'clients/:id/edit',

                    name: 'clients.edit',

                    component: () => import('@/views/clients/ClientSavePage.vue'),

                    meta: {
                        breadcrumb: 'Editar cliente',

                        permission: 'clients.update',
                    },
                },

                {
                    path: 'folders',

                    name: 'folders',

                    component: () => import('@/views/folders/FolderListPage.vue'),

                    meta: {
                        breadcrumb: 'Pastas',

                        permission: 'folders.view',
                    },
                },

                {
                    path: 'folders/new',

                    name: 'folders.create',

                    component: () => import('@/views/folders/FolderSavePage.vue'),

                    meta: {
                        breadcrumb: 'Nova pasta',

                        permission: 'folders.create',
                    },
                },

                {
                    path: 'folders/:id',

                    name: 'folders.show',

                    component: () => import('@/views/folders/FolderShowPage.vue'),

                    meta: {
                        breadcrumb: 'Detalhes da pasta',

                        permission: 'folders.view',
                    },
                },

                {
                    path: 'folders/:id/edit',

                    name: 'folders.edit',

                    component: () => import('@/views/folders/FolderSavePage.vue'),

                    meta: {
                        breadcrumb: 'Editar pasta',

                        permission: 'folders.update',
                    },
                },

                {
                    path: 'team',

                    name: 'organization-members',

                    component: () =>
                        import('@/views/organization-members/OrganizationMemberListPage.vue'),

                    meta: {
                        breadcrumb: 'Equipe',

                        permission: 'organization-members.view',
                    },
                },

                {
                    path: 'publications',
                    name: 'publications',
                    component: () => import('@/views/publications/PublicationListPage.vue'),
                    meta: {
                        breadcrumb: 'Publicações',
                        permission: 'publications.view',
                    },
                },

                {
                    path: 'publications/monitoring',
                    name: 'publications.monitoring',
                    component: () => import('@/views/publications/MonitoredBarRegistrationPage.vue'),
                    meta: {
                        breadcrumb: 'OABs monitoradas',
                        permission: 'publications.view',
                    },
                },
            ],
        },
    ],
})

router.beforeEach(authGuard)

router.beforeEach(permissionGuard)

export default router
