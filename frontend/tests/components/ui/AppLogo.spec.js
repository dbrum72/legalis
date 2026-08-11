import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'

import AppLogo from '@/components/ui/AppLogo/index.vue'

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            {
                path: '/',
                name: 'dashboard',
                component: {
                    template: '<div>Dashboard</div>',
                },
            },
            {
                path: '/home',
                name: 'home',
                component: {
                    template: '<div>Home</div>',
                },
            },
        ],
    })
}

describe('AppLogo', () => {
    async function mountComponent(props = {}) {
        const router = createTestRouter()

        await router.push('/')
        await router.isReady()

        const wrapper = mount(AppLogo, {
            props,
            global: {
                plugins: [router],
            },
        })

        return {
            wrapper,
            router,
        }
    }

    it('renderiza RouterLink', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('a').exists()).toBe(true)
    })

    it('renderiza texto Legalis por padrão', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('.app-logo__text').text()).toBe('Legalis')
    })

    it('permite personalizar o texto', async () => {
        const { wrapper } = await mountComponent({
            text: 'Portal Jurídico',
        })

        expect(wrapper.get('.app-logo__text').text()).toBe('Portal Jurídico')
    })

    it('aplica classe app-logo', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('a').classes()).toContain('app-logo')
    })

    it('usa dashboard como destino padrão', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('a').attributes('href')).toBe('/')
    })

    it('aceita destino por objeto', async () => {
        const { wrapper } = await mountComponent({
            to: {
                name: 'home',
            },
        })

        expect(wrapper.get('a').attributes('href')).toBe('/home')
    })

    it('aceita destino por string', async () => {
        const { wrapper } = await mountComponent({
            to: '/home',
        })

        expect(wrapper.get('a').attributes('href')).toBe('/home')
    })

    it('aplica aria-label padrão', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('a').attributes('aria-label')).toBe('Legalis — ir para o início')
    })

    it('permite personalizar aria-label', async () => {
        const { wrapper } = await mountComponent({
            ariaLabel: 'Voltar para o painel principal',
        })

        expect(wrapper.get('a').attributes('aria-label')).toBe('Voltar para o painel principal')
    })

    it('navega ao clicar', async () => {
        const { wrapper, router } = await mountComponent({
            to: {
                name: 'home',
            },
        })

        await wrapper.get('a').trigger('click')

        await new Promise((resolve) => setTimeout(resolve, 0))

        expect(router.currentRoute.value.name).toBe('home')
    })
})
