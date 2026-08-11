import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'

import SideBarNav from '@/components/navigation/SideBarNav/index.vue'

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
                path: '/playground',
                name: 'playground',
                component: {
                    template: '<div>Playground</div>',
                },
            },
        ],
    })
}

describe('SideBarNav', () => {
    async function mountComponent(initialRoute = '/') {
        const router = createTestRouter()

        await router.push(initialRoute)
        await router.isReady()

        const wrapper = mount(SideBarNav, {
            global: {
                plugins: [router],
            },
        })

        return {
            wrapper,
            router,
        }
    }

    it('renderiza nav', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('nav').exists()).toBe(true)
    })

    it('aplica classe sidebar-nav', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('nav').classes()).toContain('sidebar-nav')
    })

    it('aplica aria-label da navegação principal', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('nav').attributes('aria-label')).toBe('Navegação principal')
    })

    it('renderiza todos os itens do menu', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.findAll('.sidebar-item')).toHaveLength(2)
    })

    it('renderiza Dashboard', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Dashboard')
    })

    it('renderiza Playground', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Playground')
    })

    it('renderiza ícone dashboard', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.find('.lucide-layout-dashboard').exists()).toBe(true)
    })

    it('renderiza ícone playground', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.find('.lucide-flask-conical').exists()).toBe(true)
    })

    it('marca dashboard como ativo na rota dashboard', async () => {
        const { wrapper } = await mountComponent('/')

        const links = wrapper.findAll('.sidebar-item')

        expect(links[0].classes()).toContain('router-link-exact-active')
    })

    it('marca playground como ativo na rota playground', async () => {
        const { wrapper } = await mountComponent('/playground')

        const links = wrapper.findAll('.sidebar-item')

        expect(links[1].classes()).toContain('router-link-exact-active')
    })
})
