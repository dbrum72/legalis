import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import { createMemoryHistory, createRouter } from 'vue-router'

import DashboardPage from '@/views/dashboard/DashboardPage.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useDashboardStore } from '@/stores/dashboard.js'

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/',
                name: 'dashboard',
                component: DashboardPage,
            },

            {
                path: '/clients',
                name: 'clients',
                component: {
                    template: '<div>Clientes</div>',
                },
            },

            {
                path: '/clients/new',
                name: 'clients.create',
                component: {
                    template: '<div>Novo cliente</div>',
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
                path: '/folders/new',
                name: 'folders.create',
                component: {
                    template: '<div>Nova pasta</div>',
                },
            },
        ],
    })
}

function findButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

async function mountPage({
    permissions = [],
    summary = {
        clients: 0,
        folders: 0,
        active_members: 0,
    },
    recentFolders = [],
    fetchError = null,
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push('/')

    await router.isReady()

    const authStore = useAuthStore()

    authStore.permissions = permissions

    const dashboardStore = useDashboardStore()

    dashboardStore.summary = summary

    dashboardStore.recentFolders = recentFolders

    const fetchDashboardSpy = vi.spyOn(dashboardStore, 'fetchDashboard')

    if (fetchError) {
        fetchDashboardSpy.mockRejectedValue(fetchError)
    } else {
        fetchDashboardSpy.mockResolvedValue({
            summary: dashboardStore.summary,

            recent_folders: dashboardStore.recentFolders,
        })
    }

    const wrapper = mount(DashboardPage, {
        global: {
            plugins: [pinia, router],
        },
    })

    await flushPromises()

    return {
        wrapper,
        router,
        authStore,
        dashboardStore,
        fetchDashboardSpy,
    }
}

describe('DashboardPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('carrega dashboard ao montar', async () => {
        const { fetchDashboardSpy } = await mountPage()

        expect(fetchDashboardSpy).toHaveBeenCalledTimes(1)
    })

    it('renderiza titulo e descricao da pagina', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Dashboard')

        expect(wrapper.text()).toContain('Acompanhe um resumo do escritório.')
    })

    it('renderiza resumo do escritorio', async () => {
        const { wrapper } = await mountPage({
            summary: {
                clients: 12,
                folders: 8,
                active_members: 4,
            },
        })

        expect(wrapper.text()).toContain('Clientes')

        expect(wrapper.text()).toContain('12')

        expect(wrapper.text()).toContain('Pastas')

        expect(wrapper.text()).toContain('8')

        expect(wrapper.text()).toContain('Membros ativos')

        expect(wrapper.text()).toContain('4')
    })

    it('renderiza pastas recentes', async () => {
        const { wrapper } = await mountPage({
            recentFolders: [
                {
                    id: 10,

                    name: 'Ação indenizatória',

                    process_number: '5000000-00.2026.8.21.0001',

                    created_at: '2026-08-17T10:00:00.000000Z',
                },

                {
                    id: 11,

                    name: 'Atendimento extrajudicial',

                    process_number: null,

                    created_at: '2026-08-16T10:00:00.000000Z',
                },
            ],
        })

        expect(wrapper.text()).toContain('Pastas recentes')

        expect(wrapper.text()).toContain('Ação indenizatória')

        expect(wrapper.text()).toContain('5000000-00.2026.8.21.0001')

        expect(wrapper.text()).toContain('Atendimento extrajudicial')
    })

    it('renderiza numero de processo vazio sem quebrar', async () => {
        const { wrapper } = await mountPage({
            recentFolders: [
                {
                    id: 10,

                    name: 'Pasta administrativa',

                    process_number: null,

                    created_at: '2026-08-17T10:00:00.000000Z',
                },
            ],
        })

        expect(wrapper.text()).toContain('Pasta administrativa')

        expect(wrapper.text()).toContain('—')
    })

    it('renderiza estado vazio quando nao existem pastas recentes', async () => {
        const { wrapper } = await mountPage({
            recentFolders: [],
        })

        expect(wrapper.text()).toContain('Nenhuma pasta recente.')
    })

    it('exibe erro quando carregamento do dashboard falha', async () => {
        const { wrapper } = await mountPage({
            fetchError: new Error('Falha ao carregar'),
        })

        expect(wrapper.text()).toContain('Não foi possível carregar o resumo do escritório.')

        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })

    it('nao mostra Novo cliente sem clients.create', async () => {
        const { wrapper } = await mountPage({
            permissions: [],
        })

        expect(findButton(wrapper, 'Novo cliente')).toBeUndefined()
    })

    it('mostra Novo cliente com clients.create', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.create'],
        })

        expect(findButton(wrapper, 'Novo cliente')).toBeTruthy()
    })

    it('navega para cadastro de cliente', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['clients.create'],
        })

        const button = findButton(wrapper, 'Novo cliente')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('clients.create')
    })

    it('nao mostra Nova pasta sem folders.create', async () => {
        const { wrapper } = await mountPage({
            permissions: [],
        })

        expect(findButton(wrapper, 'Nova pasta')).toBeUndefined()
    })

    it('mostra Nova pasta com folders.create', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.create'],
        })

        expect(findButton(wrapper, 'Nova pasta')).toBeTruthy()
    })

    it('navega para cadastro de pasta', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['folders.create'],
        })

        const button = findButton(wrapper, 'Nova pasta')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.create')
    })

    it('mostra acoes rapidas de forma independente conforme permissoes', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.create'],
        })

        expect(findButton(wrapper, 'Novo cliente')).toBeTruthy()

        expect(findButton(wrapper, 'Nova pasta')).toBeUndefined()
    })
})
