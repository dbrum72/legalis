import { createRouter, createWebHistory } from 'vue-router'

import { authGuard } from './guards/auth.js'

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
            ],
        },
    ],
})

router.beforeEach(authGuard)

export default router
