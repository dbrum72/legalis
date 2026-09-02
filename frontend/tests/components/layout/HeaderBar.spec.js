import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/auth.js', () => ({
    context: vi.fn(),
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
vi.mock('@/api/tenant.js', () => ({
    getCurrentTenant: vi.fn(),
    setCurrentTenant: vi.fn(),
    removeCurrentTenant: vi.fn(),
}))

import HeaderBar from '@/components/layout/HeaderBar/index.vue'
import { useAuthStore } from '@/stores/auth.js'

async function mountComponent(user = {}) {
    const pinia = createPinia()
    setActivePinia(pinia)
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/', name: 'dashboard', component: { template: '<div />' } },
            { path: '/login', name: 'login', component: { template: '<div />' } },
        ],
    })
    await router.push('/')
    await router.isReady()

    const authStore = useAuthStore()
    authStore.token = 'jwt-token'
    authStore.user = { id: 1, name: 'Super Admin', email: 'super-admin@legalis.local', ...user }
    authStore.roles = ['socio-administrador']

    const wrapper = mount(HeaderBar, {
        props: { sidebarOpen: true },
        global: { plugins: [pinia, router] },
    })
    return { wrapper, router, authStore }
}

describe('HeaderBar', () => {
    beforeEach(() => vi.clearAllMocks())

    it('renderiza saudação, usuário, iniciais e função', async () => {
        const { wrapper } = await mountComponent()
        expect(wrapper.text()).toContain('Super Admin')
        expect(wrapper.text()).toContain('SA')
        expect(wrapper.text()).toContain('Socio Administrador')
        expect(wrapper.text()).toContain('Último acesso: Hoje')
    })

    it('formata a data do último acesso', async () => {
        const { wrapper } = await mountComponent({ last_login_at: '2026-08-31T14:30:00-03:00' })
        expect(wrapper.text()).toContain('31/08/2026')
    })

    it('expõe o estado do menu lateral e emite alternância', async () => {
        const { wrapper } = await mountComponent()
        const button = wrapper.get('[aria-label="Alternar menu lateral"]')
        expect(button.attributes('aria-expanded')).toBe('true')
        await button.trigger('click')
        expect(wrapper.emitted('toggle-sidebar')).toHaveLength(1)
    })

    it('renderiza notificações e configurações', async () => {
        const { wrapper } = await mountComponent()
        expect(wrapper.get('[aria-label="Notificações, 3 não lidas"]').exists()).toBe(true)
        expect(wrapper.get('[aria-label="Configurações"]').exists()).toBe(true)
    })

    it('executa logout e retorna ao login', async () => {
        const { wrapper, router, authStore } = await mountComponent()
        vi.spyOn(authStore, 'logout').mockResolvedValue()
        await wrapper.get('[aria-label="Sair da aplicação"]').trigger('click')
        await vi.waitFor(() => {
            expect(authStore.logout).toHaveBeenCalledTimes(1)
            expect(router.currentRoute.value.name).toBe('login')
        })
    })
})
