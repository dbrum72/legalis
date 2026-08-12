import { beforeEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import HeaderBar from '@/components/layout/HeaderBar/index.vue'
import { useAuthStore } from '@/stores/auth.js'

vi.mock('@/api/auth.js', () => ({
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    refresh: vi.fn(),
}))

vi.mock('@/api/auth-token.js', () => ({
    getAccessToken: vi.fn(),
    setAccessToken: vi.fn(),
    removeAccessToken: vi.fn(),
}))

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
                meta: {
                    breadcrumb: 'Dashboard',
                },
            },
            {
                path: '/playground',
                name: 'playground',
                component: {
                    template: '<div>Playground</div>',
                },
                meta: {
                    breadcrumb: 'Playground',
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

describe('HeaderBar', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    async function mountComponent(initialRoute = '/', authenticated = false) {
        const pinia = createPinia()
        setActivePinia(pinia)

        const router = createTestRouter()

        await router.push(initialRoute)
        await router.isReady()

        const authStore = useAuthStore()

        if (authenticated) {
            authStore.token = 'jwt-token'
            authStore.user = {
                id: 1,
                name: 'Super Admin',
                email: 'super-admin@legalis.local',
            }
            authStore.roles = ['super-admin']
            authStore.permissions = ['clients.view']
            authStore.hydrated = true
        }

        const wrapper = mount(HeaderBar, {
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

    it('renderiza header', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.element.tagName.toLowerCase()).toBe('header')
    })

    it('aplica classe app-header', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.classes()).toContain('app-header')
    })

    it('aplica classe app-header-bar', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.classes()).toContain('app-header-bar')
    })

    it('renderiza região inicial', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.find('.app-header-bar__start').exists()).toBe(true)
    })

    it('renderiza AppBreadcrumb', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.find('.breadcrumb').exists()).toBe(true)
    })

    it('renderiza breadcrumb da rota atual', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Dashboard')
    })

    it('reage à mudança de rota', async () => {
        const { wrapper, router } = await mountComponent()

        await router.push({
            name: 'playground',
        })

        expect(wrapper.text()).toContain('Playground')

        expect(wrapper.text()).not.toContain('Dashboard')
    })

    it('renderiza região final', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.find('.app-header-bar__end').exists()).toBe(true)
    })

    it('mantém região inicial antes da região final', async () => {
        const { wrapper } = await mountComponent()

        const children = Array.from(wrapper.element.children)

        expect(children).toHaveLength(2)

        expect(children[0].classList).toContain('app-header-bar__start')

        expect(children[1].classList).toContain('app-header-bar__end')
    })

    it('não renderiza usuário quando não autenticado', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.find('.app-header-bar__user').exists()).toBe(false)

        expect(wrapper.text()).not.toContain('Sair')
    })

    it('renderiza nome do usuário autenticado', async () => {
        const { wrapper } = await mountComponent('/', true)

        expect(wrapper.get('.app-header-bar__user-name').text()).toBe('Super Admin')
    })

    it('renderiza botão Sair para usuário autenticado', async () => {
        const { wrapper } = await mountComponent('/', true)

        expect(wrapper.text()).toContain('Sair')
    })

    it('chama authStore.logout ao clicar em Sair', async () => {
        const { wrapper, authStore } = await mountComponent('/', true)

        const logoutSpy = vi.spyOn(authStore, 'logout').mockResolvedValue()

        const button = wrapper.findAll('button').find((item) => item.text() === 'Sair')

        expect(button).toBeTruthy()

        await button.trigger('click')

        expect(logoutSpy).toHaveBeenCalledTimes(1)
    })

    it('redireciona para login após logout', async () => {
        const { wrapper, router, authStore } = await mountComponent('/', true)

        vi.spyOn(authStore, 'logout').mockResolvedValue()

        const button = wrapper.findAll('button').find((item) => item.text() === 'Sair')

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('login')
        })
    })
})
