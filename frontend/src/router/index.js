import { createRouter, createWebHistory } from 'vue-router'

import { authGuard } from './guards/auth.js'
import { permissionGuard } from '@/router/guards/permission.js'

import DefaultLayout from '@/layouts/DefaultLayout.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),

    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('@/views/auth/LoginPage.vue'),
            meta: {
                guestOnly: true,
            },
        },
        {
            path: '/',
            component: DefaultLayout,
            meta: {
                requiresAuth: true,
            },

            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('@/views/dashboard/DashboardPage.vue'),
                    meta: {
                        breadcrumb: 'Dashboard',
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
                    path: 'folders/:id/edit',
                    name: 'folders.edit',
                    component: () => import('@/views/folders/FolderSavePage.vue'),
                    meta: {
                        breadcrumb: 'Editar pasta',
                        permission: 'folders.update',
                    },
                },
            ],
        },
    ],
})

router.beforeEach(authGuard)
router.beforeEach(permissionGuard)

export default router
