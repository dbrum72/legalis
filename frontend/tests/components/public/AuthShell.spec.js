import { describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import AuthShell from '@/components/public/AuthShell.vue'

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
        ],
    })
}

async function mountShell() {
    const router = createTestRouter()

    await router.push('/login')

    await router.isReady()

    const wrapper = mount(AuthShell, {
        slots: {
            default: '<div data-testid="auth-content">Conteúdo de autenticação</div>',
        },

        global: {
            plugins: [router],
        },
    })

    return {
        wrapper,
        router,
    }
}

describe('AuthShell', () => {
    it('renderiza estrutura de autenticação', async () => {
        const { wrapper } = await mountShell()

        expect(wrapper.get('[data-testid="auth-shell"]').exists()).toBe(true)

        expect(wrapper.get('[data-testid="auth-shell-content"]').exists()).toBe(true)
    })

    it('renderiza conteúdo recebido pelo slot', async () => {
        const { wrapper } = await mountShell()

        expect(wrapper.get('[data-testid="auth-content"]').text()).toBe('Conteúdo de autenticação')
    })

    it('exibe identidade Legalis', async () => {
        const { wrapper } = await mountShell()

        expect(wrapper.text()).toContain('Legalis')
    })

    it('logo direciona para home pública', async () => {
        const { wrapper } = await mountShell()

        const logo = wrapper.get('[data-testid="auth-shell-logo"]')

        expect(logo.attributes('href')).toBe('/')
    })

    it('exibe mensagem institucional', async () => {
        const { wrapper } = await mountShell()

        expect(wrapper.get('[data-testid="auth-shell-presentation"]').text()).toContain(
            'Gestão jurídica',
        )
    })

    it('mantém conteúdo de autenticação em região própria', async () => {
        const { wrapper } = await mountShell()

        expect(
            wrapper
                .get('[data-testid="auth-shell-content"]')
                .get('[data-testid="auth-content"]')
                .exists(),
        ).toBe(true)
    })
})
