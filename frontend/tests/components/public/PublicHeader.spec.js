import { beforeEach, describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import PublicHeader from '@/components/public/PublicHeader.vue'

import { useAuthStore } from '@/stores/auth.js'

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/',

                name: 'home',

                component: {
                    template: '<div>Home</div>',
                },
            },

            {
                path: '/login',

                name: 'login',

                component: {
                    template: '<div>Login</div>',
                },
            },

            {
                path: '/register',

                name: 'register',

                component: {
                    template: '<div>Cadastro</div>',
                },
            },

            {
                path: '/dashboard',

                name: 'dashboard',

                component: {
                    template: '<div>Dashboard</div>',
                },
            },

            {
                path: '/organizations/select',

                name: 'organizations.select',

                component: {
                    template: '<div>Selecionar organização</div>',
                },
            },
        ],
    })
}

async function mountHeader() {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push('/')

    await router.isReady()

    const wrapper = mount(PublicHeader, {
        global: {
            plugins: [pinia, router],
        },
    })

    return {
        wrapper,
        router,

        authStore: useAuthStore(),
    }
}

describe('PublicHeader', () => {
    beforeEach(() => {
        localStorage.clear()
    })

    it('exibe entrar e criar conta para visitante', async () => {
        const { wrapper } = await mountHeader()

        expect(wrapper.get('[data-testid="public-login-link"]').attributes('href')).toBe('/login')

        expect(wrapper.get('[data-testid="public-register-link"]').attributes('href')).toBe(
            '/register',
        )

        expect(wrapper.find('[data-testid="public-dashboard-link"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="public-organization-link"]').exists()).toBe(false)
    })

    it('exibe acesso ao sistema para usuario autenticado com contexto', async () => {
        const { wrapper, authStore } = await mountHeader()

        authStore.token = 'jwt-token'

        authStore.user = {
            id: 1,
            name: 'Usuário',
            email: 'usuario@legalis.test',
        }

        authStore.organization = {
            id: 10,
            name: 'Legalis Advocacia',
            slug: 'legalis-advocacia',
        }

        authStore.contextLoaded = true

        await wrapper.vm.$nextTick()

        expect(wrapper.find('[data-testid="public-login-link"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="public-register-link"]').exists()).toBe(false)

        const dashboardLink = wrapper.get('[data-testid="public-dashboard-link"]')

        expect(dashboardLink.attributes('href')).toBe('/dashboard')

        expect(dashboardLink.text()).toContain('Acessar sistema')
    })

    it('exibe selecao de organizacao para autenticado sem contexto', async () => {
        const { wrapper, authStore } = await mountHeader()

        authStore.token = 'jwt-token'

        authStore.user = {
            id: 1,
            name: 'Usuário',
            email: 'usuario@legalis.test',
        }

        authStore.contextLoaded = false

        await wrapper.vm.$nextTick()

        expect(wrapper.find('[data-testid="public-login-link"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="public-register-link"]').exists()).toBe(false)

        const organizationLink = wrapper.get('[data-testid="public-organization-link"]')

        expect(organizationLink.attributes('href')).toBe('/organizations/select')

        expect(organizationLink.text()).toContain('Selecionar organização')
    })

    it('mantem navegacao institucional para recursos e beneficios', async () => {
        const { wrapper } = await mountHeader()

        const resources = wrapper.get('[data-testid="public-resources-link"]')

        const benefits = wrapper.get('[data-testid="public-benefits-link"]')

        expect(resources.attributes('href')).toBe('/#recursos')

        expect(benefits.attributes('href')).toBe('/#beneficios')
    })
})
