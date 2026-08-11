import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'

import SideBarItem from '@/components/navigation/SideBarItem/index.vue'

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

describe('SideBarItem', () => {
    async function mountComponent(
        item = {
            id: 'dashboard',
            name: 'dashboard',
            label: 'Dashboard',
            icon: 'dashboard',
        },
        initialRoute = '/',
    ) {
        const router = createTestRouter()

        await router.push(initialRoute)
        await router.isReady()

        const wrapper = mount(SideBarItem, {
            props: {
                item,
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

    it('renderiza RouterLink', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('a').exists()).toBe(true)
    })

    it('aplica classe sidebar-item', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('a').classes()).toContain('sidebar-item')
    })

    it('renderiza label', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('.sidebar-item__label').text()).toBe('Dashboard')
    })

    it('usa item.name como destino', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.get('a').attributes('href')).toBe('/')
    })

    it('renderiza ícone quando item.icon existe', async () => {
        const { wrapper } = await mountComponent()

        const svg = wrapper.get('.sidebar-item__icon')

        expect(svg.exists()).toBe(true)

        expect(svg.classes()).toContain('lucide-layout-dashboard')
    })

    it('não renderiza ícone quando item.icon está ausente', async () => {
        const { wrapper } = await mountComponent({
            id: 'playground',
            name: 'playground',
            label: 'Playground',
        })

        expect(wrapper.find('.sidebar-item__icon').exists()).toBe(false)
    })

    it('renderiza ícone do registry para playground', async () => {
        const { wrapper } = await mountComponent({
            id: 'playground',
            name: 'playground',
            label: 'Playground',
            icon: 'playground',
        })

        expect(wrapper.get('.sidebar-item__icon').classes()).toContain('lucide-flask-conical')
    })

    it('aplica classe ativa na rota correspondente', async () => {
        const { wrapper } = await mountComponent(
            {
                id: 'dashboard',
                name: 'dashboard',
                label: 'Dashboard',
                icon: 'dashboard',
            },
            '/',
        )

        expect(wrapper.get('a').classes()).toContain('router-link-exact-active')
    })

    it('não aplica classe ativa quando rota é diferente', async () => {
        const { wrapper } = await mountComponent(
            {
                id: 'playground',
                name: 'playground',
                label: 'Playground',
                icon: 'playground',
            },
            '/',
        )

        expect(wrapper.get('a').classes()).not.toContain('router-link-exact-active')
    })

    it('navega ao clicar', async () => {
        const { wrapper, router } = await mountComponent({
            id: 'playground',
            name: 'playground',
            label: 'Playground',
            icon: 'playground',
        })

        await wrapper.get('a').trigger('click')

        await new Promise((resolve) => setTimeout(resolve, 0))

        expect(router.currentRoute.value.name).toBe('playground')
    })
})
