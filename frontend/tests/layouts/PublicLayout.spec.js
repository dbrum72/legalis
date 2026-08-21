import { describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import PublicLayout from '@/layouts/PublicLayout.vue'

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/',

                component: PublicLayout,

                children: [
                    {
                        path: '',

                        name: 'home',

                        component: {
                            template: '<main data-testid="public-content">Landing Page</main>',
                        },
                    },
                ],
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

async function mountLayout() {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push('/')

    await router.isReady()

    const wrapper = mount(PublicLayout, {
        global: {
            plugins: [pinia, router],
        },
    })

    return {
        wrapper,
        router,
    }
}

describe('PublicLayout', () => {
    it('renderiza estrutura publica', async () => {
        const { wrapper } = await mountLayout()

        expect(wrapper.get('[data-testid="public-layout"]').exists()).toBe(true)

        expect(wrapper.get('[data-testid="public-header"]').exists()).toBe(true)

        expect(wrapper.get('[data-testid="public-main"]').exists()).toBe(true)

        expect(wrapper.get('[data-testid="public-footer"]').exists()).toBe(true)
    })

    it('renderiza conteudo da rota filha', async () => {
        const { wrapper } = await mountLayout()

        expect(wrapper.get('[data-testid="public-content"]').text()).toBe('Landing Page')
    })

    it('possui acesso para login', async () => {
        const { wrapper } = await mountLayout()

        const link = wrapper.get('[data-testid="public-login-link"]')

        expect(link.attributes('href')).toBe('/login')

        expect(link.text()).toContain('Entrar')
    })

    it('possui acesso para criacao de conta', async () => {
        const { wrapper } = await mountLayout()

        const link = wrapper.get('[data-testid="public-register-link"]')

        expect(link.attributes('href')).toBe('/register')

        expect(link.text()).toContain('Criar conta')
    })

    it('exibe identidade Legalis', async () => {
        const { wrapper } = await mountLayout()

        expect(wrapper.text()).toContain('Legalis')
    })
})
