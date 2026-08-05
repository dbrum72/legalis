import { createRouter, createWebHistory } from 'vue-router'

import DefaultLayout from '@/layouts/DefaultLayout.vue'
import DashboardPage from '@/views/dashboard/DashboardPage.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),

    routes: [
        {
            path: '/',
            component: DefaultLayout,

            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: DashboardPage,
                    meta: { breadcrumb: 'Dashboard' },
                },
                {
                    path: '/playground',
                    name: 'playground',
                    component: () => import('@/views/playground/PlaygroundPage.vue'),
                },
            ],
        },
    ],
})

export default router
