import { describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import LandingPage from '@/views/public/LandingPage.vue'

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/',

                name: 'home',

                component: LandingPage,
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
        ],
    })
}

async function mountPage() {
    const router = createTestRouter()

    await router.push('/')

    await router.isReady()

    const wrapper = mount(LandingPage, {
        global: {
            plugins: [router],
        },
    })

    return {
        wrapper,
        router,
    }
}

describe('LandingPage', () => {
    it('renderiza proposta principal do Legalis', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.get('[data-testid="landing-hero"]').exists()).toBe(true)

        expect(wrapper.get('h1').text()).toContain('gestão jurídica')
    })

    it('possui chamada principal para criar conta', async () => {
        const { wrapper } = await mountPage()

        const link = wrapper.get('[data-testid="landing-primary-cta"]')

        expect(link.attributes('href')).toBe('/register')

        expect(link.text()).toContain('Começar agora')
    })

    it('possui chamada secundaria para conhecer recursos', async () => {
        const { wrapper } = await mountPage()

        const link = wrapper.get('[data-testid="landing-secondary-cta"]')

        expect(link.attributes('href')).toBe('#recursos')
    })

    it('apresenta recursos existentes do sistema', async () => {
        const { wrapper } = await mountPage()

        const resources = wrapper.get('[data-testid="landing-resources"]')

        expect(resources.text()).toContain('Clientes')

        expect(resources.text()).toContain('Pastas')

        expect(resources.text()).toContain('Agenda')

        expect(resources.text()).toContain('Equipe')
    })

    it('possui secao de beneficios', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.get('#beneficios').exists()).toBe(true)
    })

    it('apresenta representacao visual do produto', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.get('[data-testid="landing-product-preview"]').exists()).toBe(true)
    })

    it('possui chamada final para criacao de conta', async () => {
        const { wrapper } = await mountPage()

        const link = wrapper.get('[data-testid="landing-final-cta"]')

        expect(link.attributes('href')).toBe('/register')
    })
})
