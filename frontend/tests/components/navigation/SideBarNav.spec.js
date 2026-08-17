import { beforeEach, describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import { createMemoryHistory, createRouter } from 'vue-router'

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
                path: '/clients',
                name: 'clients',
                component: {
                    template: '<div>Clientes</div>',
                },
            },

            {
                path: '/folders',
                name: 'folders',
                component: {
                    template: '<div>Pastas</div>',
                },
            },

            {
                path: '/organization-members',
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

async function mountNav({ permissions = [], route = '/' } = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()

    authStore.permissions = permissions

    const router = createTestRouter()

    await router.push(route)

    await router.isReady()

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

function linkByLabel(wrapper, label) {
    return wrapper.findAll('a').find((link) => link.text().trim() === label)
}

describe('SideBarNav', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
    })

    it('renderiza elemento de navegacao principal', async () => {
        const { wrapper } = await mountNav()

        const nav = wrapper.get('nav')

        expect(nav.classes()).toContain('sidebar-nav')

        expect(nav.attributes('aria-label')).toBe('Navegação principal')
    })

    it('sempre mostra Dashboard', async () => {
        const { wrapper } = await mountNav()

        expect(wrapper.text()).toContain('Dashboard')
    })

    it('nao mostra Clientes sem clients.view', async () => {
        const { wrapper } = await mountNav()

        expect(wrapper.text()).not.toContain('Clientes')
    })

    it('mostra Clientes com clients.view', async () => {
        const { wrapper } = await mountNav({
            permissions: ['clients.view'],
        })

        expect(wrapper.text()).toContain('Clientes')
    })

    it('nao mostra Pastas sem folders.view', async () => {
        const { wrapper } = await mountNav()

        expect(wrapper.text()).not.toContain('Pastas')
    })

    it('mostra Pastas com folders.view', async () => {
        const { wrapper } = await mountNav({
            permissions: ['folders.view'],
        })

        expect(wrapper.text()).toContain('Pastas')
    })

    it('nao mostra Equipe sem organization-members.view', async () => {
        const { wrapper } = await mountNav()

        expect(wrapper.text()).not.toContain('Equipe')
    })

    it('mostra Equipe com organization-members.view', async () => {
        const { wrapper } = await mountNav({
            permissions: ['organization-members.view'],
        })

        expect(wrapper.text()).toContain('Equipe')
    })

    it('filtra itens de forma independente pelas permissoes', async () => {
        const { wrapper } = await mountNav({
            permissions: ['clients.view', 'organization-members.view'],
        })

        expect(wrapper.text()).toContain('Dashboard')

        expect(wrapper.text()).toContain('Clientes')

        expect(wrapper.text()).not.toContain('Pastas')

        expect(wrapper.text()).toContain('Equipe')
    })

    it('nao exibe Playground na navegacao operacional', async () => {
        const { wrapper } = await mountNav({
            permissions: ['clients.view', 'folders.view', 'organization-members.view'],
        })

        expect(wrapper.text()).not.toContain('Playground')
    })

    it('Dashboard aponta para a rota dashboard', async () => {
        const { wrapper } = await mountNav()

        const link = linkByLabel(wrapper, 'Dashboard')

        expect(link).toBeTruthy()

        expect(link.attributes('href')).toBe('/')
    })

    it('Clientes aponta para a rota clients', async () => {
        const { wrapper } = await mountNav({
            permissions: ['clients.view'],
        })

        const link = linkByLabel(wrapper, 'Clientes')

        expect(link).toBeTruthy()

        expect(link.attributes('href')).toBe('/clients')
    })

    it('Pastas aponta para a rota folders', async () => {
        const { wrapper } = await mountNav({
            permissions: ['folders.view'],
        })

        const link = linkByLabel(wrapper, 'Pastas')

        expect(link).toBeTruthy()

        expect(link.attributes('href')).toBe('/folders')
    })

    it('Equipe aponta para a rota organization-members', async () => {
        const { wrapper } = await mountNav({
            permissions: ['organization-members.view'],
        })

        const link = linkByLabel(wrapper, 'Equipe')

        expect(link).toBeTruthy()

        expect(link.attributes('href')).toBe('/organization-members')
    })

    it('marca Dashboard como ativo na rota inicial', async () => {
        const { wrapper } = await mountNav({
            route: '/',
        })

        const link = linkByLabel(wrapper, 'Dashboard')

        expect(link.classes()).toContain('router-link-exact-active')
    })

    it('marca Clientes como ativo em sua rota', async () => {
        const { wrapper } = await mountNav({
            route: '/clients',

            permissions: ['clients.view'],
        })

        const link = linkByLabel(wrapper, 'Clientes')

        expect(link.classes()).toContain('router-link-exact-active')
    })
})
