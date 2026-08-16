import { beforeEach, describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import SideBarNav from '@/components/navigation/SideBarNav/index.vue'

import { useAuthStore } from '@/stores/auth.js'

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
                path: '/team',

                name: 'organization-members',

                component: {
                    template: '<div>Equipe</div>',
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
    beforeEach(() => {
        const pinia = createPinia()

        setActivePinia(pinia)
    })

    async function mountComponent(initialRoute = '/', permissions = []) {
        const pinia = createPinia()

        setActivePinia(pinia)

        const router = createTestRouter()

        await router.push(initialRoute)

        await router.isReady()

        const authStore = useAuthStore()

        authStore.permissions = permissions

        const wrapper = mount(SideBarNav, {
            global: {
                plugins: [pinia, router],
            },
        })

        return {
            wrapper,
            router,
            authStore,
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

    it('renderiza itens públicos do menu sem permissão de equipe', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.findAll('.sidebar-item')).toHaveLength(2)

        expect(wrapper.text()).toContain('Dashboard')

        expect(wrapper.text()).toContain('Playground')
    })

    it('não renderiza equipe sem permission', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).not.toContain('Equipe')
    })

    it('renderiza equipe quando possui organization-members.view', async () => {
        const { wrapper } = await mountComponent('/', ['organization-members.view'])

        expect(wrapper.findAll('.sidebar-item')).toHaveLength(3)

        expect(wrapper.text()).toContain('Equipe')
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

    it('renderiza ícone de equipe quando possui permission', async () => {
        const { wrapper } = await mountComponent('/', ['organization-members.view'])

        expect(wrapper.find('.lucide-users').exists()).toBe(true)
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

    it('marca equipe como ativa na rota de membros', async () => {
        const { wrapper } = await mountComponent('/team', ['organization-members.view'])

        const links = wrapper.findAll('.sidebar-item')

        expect(links).toHaveLength(3)

        expect(links[1].text()).toContain('Equipe')

        expect(links[1].classes()).toContain('router-link-exact-active')
    })
})
