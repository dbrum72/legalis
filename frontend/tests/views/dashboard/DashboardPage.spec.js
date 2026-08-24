import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import { createMemoryHistory, createRouter } from 'vue-router'

import DashboardPage from '@/views/dashboard/DashboardPage.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useDashboardStore } from '@/stores/dashboard.js'
import { useFolderDeadlinesStore } from '@/stores/folder-deadlines.js'
import { useFolderEventsStore } from '@/stores/folder-events.js'
import { useFolderTasksStore } from '@/stores/folder-tasks.js'

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

            {
                path: '/folders/:id',
                name: 'folders.show',
                component: {
                    template: '<div>Detalhes da pasta</div>',
                },
            },
        ],
    })
}

function emptyAttention() {
    return {
        overdue_tasks: [],
        overdue_deadlines: [],
    }
}

function defaultAttention() {
    return {
        overdue_tasks: [
            {
                id: 401,
                title: 'Protocolar petição vencida',
                priority: 'high',
                due_at: '2026-08-18T18:00:00.000000Z',
                status: 'pending',

                folder: {
                    id: 10,
                    name: 'Ação indenizatória',
                    process_number: '5000000-00.2026.8.21.0001',
                },
            },
        ],

        overdue_deadlines: [
            {
                id: 501,
                title: 'Apresentar contestação vencida',
                due_at: '2026-08-18T23:59:59.000000Z',
                status: 'pending',

                folder: {
                    id: 12,
                    name: 'Ação revisional',
                    process_number: '5002222-33.2026.8.21.0022',
                },
            },
        ],
    }
}

function emptyMyWork() {
    return {
        pending_tasks: [],
        pending_deadlines: [],
        upcoming_events: [],
    }
}

function defaultMyWork() {
    return {
        pending_tasks: [
            {
                id: 801,
                title: 'Minha tarefa prioritária',
                priority: 'high',
                due_at: '2026-08-20T18:00:00.000000Z',
                status: 'pending',

                folder: {
                    id: 10,
                    name: 'Ação indenizatória',
                    process_number: '5000000-00.2026.8.21.0001',
                },
            },

            {
                id: 802,
                title: 'Minha tarefa de acompanhamento',
                priority: 'medium',
                due_at: null,
                status: 'pending',

                folder: {
                    id: 11,
                    name: 'Ação de cobrança',
                    process_number: null,
                },
            },
        ],

        pending_deadlines: [
            {
                id: 803,
                title: 'Meu prazo processual',
                due_at: '2026-08-21T23:59:59.000000Z',
                status: 'pending',

                folder: {
                    id: 12,
                    name: 'Ação revisional',
                    process_number: '5002222-33.2026.8.21.0022',
                },
            },

            {
                id: 804,
                title: 'Meu segundo prazo',
                due_at: '2026-08-25T23:59:59.000000Z',
                status: 'pending',

                folder: {
                    id: 13,
                    name: 'Ação de alimentos',
                    process_number: '5003333-44.2026.8.21.0022',
                },
            },
        ],

        upcoming_events: [
            {
                id: 805,
                type: 'hearing',
                title: 'Minha audiência',
                starts_at: '2026-08-22T19:00:00.000000Z',
                ends_at: '2026-08-22T20:00:00.000000Z',
                location: 'Fórum de Pelotas',
                status: 'scheduled',

                folder: {
                    id: 14,
                    name: 'Ação possessória',
                    process_number: '5004444-55.2026.8.21.0022',
                },
            },

            {
                id: 806,
                type: 'meeting',
                title: 'Minha reunião',
                starts_at: '2026-08-24T14:00:00.000000Z',
                ends_at: null,
                location: 'Escritório',
                status: 'scheduled',

                folder: {
                    id: 15,
                    name: 'Consultoria empresarial',
                    process_number: null,
                },
            },
        ],
    }
}

function findButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

function findButtons(wrapper, label) {
    return wrapper.findAll('button').filter((button) => button.text().trim() === label)
}

async function mountPage({
    permissions = [],

    summary = {
        clients: 0,
        folders: 0,
        active_members: 0,
        pending_tasks: 0,
        pending_deadlines: 0,
        upcoming_events: 0,
        overdue_tasks: 0,
        overdue_deadlines: 0,
    },

    attention = emptyAttention(),

    todayAgenda = [],

    recentActivity = [],

    recentFolders = [],

    myWork = emptyMyWork(),

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

    dashboardStore.attention = attention

    dashboardStore.todayAgenda = todayAgenda

    dashboardStore.recentActivity = recentActivity

    dashboardStore.recentFolders = recentFolders

    dashboardStore.myWork = myWork

    const folderTasksStore = useFolderTasksStore()

    const folderDeadlinesStore = useFolderDeadlinesStore()

    const folderEventsStore = useFolderEventsStore()

    const fetchDashboardSpy = vi.spyOn(dashboardStore, 'fetchDashboard')

    if (fetchError) {
        fetchDashboardSpy.mockRejectedValue(fetchError)
    } else {
        fetchDashboardSpy.mockResolvedValue({
            summary: dashboardStore.summary,

            attention: dashboardStore.attention,

            today_agenda: dashboardStore.todayAgenda,

            recent_activity: dashboardStore.recentActivity,

            recent_folders: dashboardStore.recentFolders,

            my_work: dashboardStore.myWork,
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
        folderDeadlinesStore,
        folderEventsStore,
        folderTasksStore,
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
                pending_tasks: 7,
                pending_deadlines: 3,
                upcoming_events: 5,
                overdue_tasks: 0,
                overdue_deadlines: 0,
            },
        })

        const text = wrapper.text()

        expect(text).toContain('Clientes')

        expect(text).toContain('12')

        expect(text).toContain('Pastas')

        expect(text).toContain('8')

        expect(text).toContain('Membros ativos')

        expect(text).toContain('4')
    })

    it('renderiza pastas recentes', async () => {
        const { wrapper } = await mountPage({
            recentFolders: [
                {
                    id: 10,
                    name: 'Ação indenizatória',
                    process_number: '5000000-00.2026.8.21.0001',
                    created_at: '2026-08-19T10:00:00.000000Z',
                },
            ],
        })

        const text = wrapper.text()

        expect(text).toContain('Ação indenizatória')

        expect(text).toContain('5000000-00.2026.8.21.0001')
    })

    it('renderiza numero de processo vazio sem quebrar', async () => {
        const { wrapper } = await mountPage({
            recentFolders: [
                {
                    id: 10,
                    name: 'Consultoria contratual',
                    process_number: null,
                    created_at: '2026-08-19T10:00:00.000000Z',
                },
            ],
        })

        expect(wrapper.text()).toContain('Consultoria contratual')
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
    })

    it('nao mostra Novo cliente sem clients.create', async () => {
        const { wrapper } = await mountPage()

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
        const { wrapper } = await mountPage()

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

    /*
    |--------------------------------------------------------------------------
    | Central de Atenção
    |--------------------------------------------------------------------------
    */

    it('renderiza central de atencao', async () => {
        const { wrapper } = await mountPage({
            summary: {
                clients: 12,
                folders: 8,
                active_members: 4,
                pending_tasks: 7,
                pending_deadlines: 3,
                upcoming_events: 5,
                overdue_tasks: 2,
                overdue_deadlines: 1,
            },
        })

        const text = wrapper.text()

        expect(text).toContain('Central de Atenção')

        expect(text).toContain('Itens que exigem ação imediata.')
    })

    it('renderiza tarefas vencidas na central de atencao', async () => {
        const { wrapper } = await mountPage({
            summary: {
                clients: 0,
                folders: 0,
                active_members: 0,
                pending_tasks: 0,
                pending_deadlines: 0,
                upcoming_events: 0,
                overdue_tasks: 2,
                overdue_deadlines: 0,
            },
        })

        const text = wrapper.text()

        expect(text).toContain('Tarefas vencidas')

        expect(text).toContain('2')
    })

    it('renderiza prazos vencidos na central de atencao', async () => {
        const { wrapper } = await mountPage({
            summary: {
                clients: 0,
                folders: 0,
                active_members: 0,
                pending_tasks: 0,
                pending_deadlines: 0,
                upcoming_events: 0,
                overdue_tasks: 0,
                overdue_deadlines: 1,
            },
        })

        const text = wrapper.text()

        expect(text).toContain('Prazos vencidos')

        expect(text).toContain('1')
    })

    it('renderiza itens detalhados da central de atencao', async () => {
        const { wrapper } = await mountPage({
            attention: defaultAttention(),
        })

        const text = wrapper.text()

        expect(text).toContain('Protocolar petição vencida')

        expect(text).toContain('Apresentar contestação vencida')
    })

    it('renderiza estados vazios dos itens da central de atencao', async () => {
        const { wrapper } = await mountPage({
            attention: emptyAttention(),
        })

        const text = wrapper.text()

        expect(text).toContain('Nenhuma tarefa vencida.')

        expect(text).toContain('Nenhum prazo vencido.')
    })

    it('navega para a pasta a partir de tarefa vencida', async () => {
        const { wrapper, router } = await mountPage({
            attention: defaultAttention(),
        })

        const button = wrapper.find('[data-testid="dashboard-attention-task-folder-10"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.show')

        expect(router.currentRoute.value.params.id).toBe('10')
    })

    it('navega para a pasta a partir de prazo vencido', async () => {
        const { wrapper, router } = await mountPage({
            attention: defaultAttention(),
        })

        const button = wrapper.find('[data-testid="dashboard-attention-deadline-folder-12"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.show')

        expect(router.currentRoute.value.params.id).toBe('12')
    })

    it('mostra acoes da central de atencao com folders.update', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.update'],

            attention: defaultAttention(),
        })

        expect(findButtons(wrapper, 'Concluir tarefa')).toHaveLength(1)

        expect(findButtons(wrapper, 'Concluir prazo')).toHaveLength(1)
    })

    it('nao mostra acoes da central de atencao sem folders.update', async () => {
        const { wrapper } = await mountPage({
            attention: defaultAttention(),
        })

        expect(findButtons(wrapper, 'Concluir tarefa')).toHaveLength(0)

        expect(findButtons(wrapper, 'Concluir prazo')).toHaveLength(0)
    })

    it('conclui tarefa vencida pela central de atencao e recarrega dashboard', async () => {
        const { wrapper, folderTasksStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            attention: defaultAttention(),
        })

        const completeSpy = vi.spyOn(folderTasksStore, 'completeTask').mockResolvedValue({
            id: 401,
            status: 'completed',
        })

        const button = findButtons(wrapper, 'Concluir tarefa')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(completeSpy).toHaveBeenCalledWith(10, 401)
        })

        await vi.waitFor(() => {
            expect(fetchDashboardSpy).toHaveBeenCalledTimes(2)
        })
    })

    it('conclui prazo vencido pela central de atencao e recarrega dashboard', async () => {
        const { wrapper, folderDeadlinesStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            attention: defaultAttention(),
        })

        const completeSpy = vi.spyOn(folderDeadlinesStore, 'completeDeadline').mockResolvedValue({
            id: 501,
            status: 'completed',
        })

        const button = findButtons(wrapper, 'Concluir prazo')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(completeSpy).toHaveBeenCalledWith(12, 501)
        })

        await vi.waitFor(() => {
            expect(fetchDashboardSpy).toHaveBeenCalledTimes(2)
        })
    })

    it('exibe erro quando conclusao de tarefa vencida pela central falha', async () => {
        const { wrapper, folderTasksStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            attention: defaultAttention(),
        })

        vi.spyOn(folderTasksStore, 'completeTask').mockRejectedValue(new Error('Falha ao concluir'))

        const button = findButtons(wrapper, 'Concluir tarefa')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível concluir a tarefa. Tente novamente.')
        })

        expect(fetchDashboardSpy).toHaveBeenCalledTimes(1)
    })

    it('exibe erro quando conclusao de prazo vencido pela central falha', async () => {
        const { wrapper, folderDeadlinesStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            attention: defaultAttention(),
        })

        vi.spyOn(folderDeadlinesStore, 'completeDeadline').mockRejectedValue(
            new Error('Falha ao concluir'),
        )

        const button = findButtons(wrapper, 'Concluir prazo')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível concluir o prazo. Tente novamente.')
        })

        expect(fetchDashboardSpy).toHaveBeenCalledTimes(1)
    })

    /*
    |--------------------------------------------------------------------------
    | Atividade recente
    |--------------------------------------------------------------------------
    */

    it('renderiza secao de atividade recente', async () => {
        const { wrapper } = await mountPage({
            recentActivity: [],
        })

        const text = wrapper.text()

        expect(text).toContain('Atividade recente')

        expect(text).toContain('Últimas atividades concluídas no escritório.')
    })

    it('renderiza atividades recentes concluídas', async () => {
        const { wrapper } = await mountPage({
            recentActivity: [
                {
                    id: 701,
                    type: 'task',
                    title: 'Revisar documentos concluída',
                    completed_at: '2026-08-19T18:30:00.000000Z',

                    folder: {
                        id: 10,
                        name: 'Ação indenizatória',
                        process_number: '5000000-00.2026.8.21.0001',
                    },
                },

                {
                    id: 702,
                    type: 'deadline',
                    title: 'Manifestação protocolada',
                    completed_at: '2026-08-19T17:30:00.000000Z',

                    folder: {
                        id: 12,
                        name: 'Ação revisional',
                        process_number: '5002222-33.2026.8.21.0022',
                    },
                },

                {
                    id: 703,
                    type: 'event',
                    title: 'Audiência realizada',
                    completed_at: '2026-08-19T16:30:00.000000Z',

                    folder: {
                        id: 13,
                        name: 'Ação de alimentos',
                        process_number: '5003333-44.2026.8.21.0022',
                    },
                },
            ],
        })

        const text = wrapper.text()

        expect(text).toContain('Revisar documentos concluída')

        expect(text).toContain('Manifestação protocolada')

        expect(text).toContain('Audiência realizada')

        expect(text).toContain('Ação indenizatória')

        expect(text).toContain('Ação revisional')

        expect(text).toContain('Ação de alimentos')
    })

    it('renderiza tipo das atividades recentes', async () => {
        const { wrapper } = await mountPage({
            recentActivity: [
                {
                    id: 701,
                    type: 'task',
                    title: 'Tarefa concluída',
                    completed_at: '2026-08-19T18:30:00.000000Z',
                    folder: null,
                },

                {
                    id: 702,
                    type: 'deadline',
                    title: 'Prazo concluído',
                    completed_at: '2026-08-19T17:30:00.000000Z',
                    folder: null,
                },

                {
                    id: 703,
                    type: 'event',
                    title: 'Compromisso concluído',
                    completed_at: '2026-08-19T16:30:00.000000Z',
                    folder: null,
                },
            ],
        })

        const activity = wrapper.find('[data-testid="dashboard-recent-activity"]')

        expect(activity.exists()).toBe(true)

        expect(activity.text()).toContain('Tarefa')

        expect(activity.text()).toContain('Prazo')

        expect(activity.text()).toContain('Compromisso')
    })

    it('renderiza estado vazio da atividade recente', async () => {
        const { wrapper } = await mountPage({
            recentActivity: [],
        })

        expect(wrapper.text()).toContain('Nenhuma atividade recente.')
    })

    it('navega para a pasta a partir de atividade recente', async () => {
        const { wrapper, router } = await mountPage({
            recentActivity: [
                {
                    id: 701,
                    type: 'task',
                    title: 'Revisar documentos concluída',
                    completed_at: '2026-08-19T18:30:00.000000Z',

                    folder: {
                        id: 10,
                        name: 'Ação indenizatória',
                        process_number: '5000000-00.2026.8.21.0001',
                    },
                },
            ],
        })

        const button = wrapper.find('[data-testid="dashboard-activity-folder-10"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.show')

        expect(router.currentRoute.value.params.id).toBe('10')
    })

    it('atividade recente nao apresenta acao de conclusao', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.update'],

            recentActivity: [
                {
                    id: 701,
                    type: 'task',
                    title: 'Atividade histórica única',
                    completed_at: '2026-08-19T18:30:00.000000Z',

                    folder: {
                        id: 10,
                        name: 'Pasta histórica única',
                        process_number: null,
                    },
                },
            ],
        })

        const activity = wrapper.find('[data-testid="dashboard-recent-activity"]')

        expect(activity.exists()).toBe(true)

        expect(activity.text()).toContain('Atividade histórica única')

        expect(
            activity.findAll('button').some((button) => button.text().includes('Concluir')),
        ).toBe(false)
    })

    /*
    |--------------------------------------------------------------------------
    | Meu trabalho
    |--------------------------------------------------------------------------
    */

    it('renderiza secao meu trabalho', async () => {
        const { wrapper } = await mountPage({
            myWork: emptyMyWork(),
        })

        const text = wrapper.text()

        expect(text).toContain('Meu trabalho')

        expect(text).toContain('Itens sob sua responsabilidade.')
    })

    it('renderiza minhas tarefas pendentes', async () => {
        const { wrapper } = await mountPage({
            myWork: defaultMyWork(),
        })

        const text = wrapper.text()

        expect(text).toContain('Minhas tarefas')

        expect(text).toContain('Minha tarefa prioritária')

        expect(text).toContain('Minha tarefa de acompanhamento')

        expect(text).toContain('Ação indenizatória')

        expect(text).toContain('Ação de cobrança')
    })

    it('renderiza meus prazos pendentes', async () => {
        const { wrapper } = await mountPage({
            myWork: defaultMyWork(),
        })

        const text = wrapper.text()

        expect(text).toContain('Meus prazos')

        expect(text).toContain('Meu prazo processual')

        expect(text).toContain('Meu segundo prazo')

        expect(text).toContain('Ação revisional')

        expect(text).toContain('Ação de alimentos')
    })

    it('renderiza meus proximos compromissos', async () => {
        const { wrapper } = await mountPage({
            myWork: defaultMyWork(),
        })

        const text = wrapper.text()

        expect(text).toContain('Meus compromissos')

        expect(text).toContain('Minha audiência')

        expect(text).toContain('Minha reunião')

        expect(text).toContain('Fórum de Pelotas')

        expect(text).toContain('Ação possessória')

        expect(text).toContain('Consultoria empresarial')
    })

    it('renderiza estados vazios de meu trabalho', async () => {
        const { wrapper } = await mountPage({
            myWork: emptyMyWork(),
        })

        const text = wrapper.text()

        expect(text).toContain('Nenhuma tarefa atribuída a você.')

        expect(text).toContain('Nenhum prazo atribuído a você.')

        expect(text).toContain('Nenhum compromisso atribuído a você.')
    })

    it('navega para a pasta a partir de minha tarefa', async () => {
        const { wrapper, router } = await mountPage({
            myWork: defaultMyWork(),
        })

        const button = wrapper.find('[data-testid="dashboard-my-work-task-folder-10"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.show')

        expect(router.currentRoute.value.params.id).toBe('10')
    })

    it('navega para a pasta a partir de meu prazo', async () => {
        const { wrapper, router } = await mountPage({
            myWork: defaultMyWork(),
        })

        const button = wrapper.find('[data-testid="dashboard-my-work-deadline-folder-12"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.show')

        expect(router.currentRoute.value.params.id).toBe('12')
    })

    it('navega para a pasta a partir de meu compromisso', async () => {
        const { wrapper, router } = await mountPage({
            myWork: defaultMyWork(),
        })

        const button = wrapper.find('[data-testid="dashboard-my-work-event-folder-14"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.show')

        expect(router.currentRoute.value.params.id).toBe('14')
    })

    it('mostra acoes de meu trabalho quando possui folders.update', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.update'],

            myWork: defaultMyWork(),
        })

        const myWork = wrapper.find('[data-testid="dashboard-my-work"]')

        expect(myWork.exists()).toBe(true)

        expect(
            myWork.findAll('button').filter((button) => button.text().trim() === 'Concluir tarefa'),
        ).toHaveLength(2)

        expect(
            myWork.findAll('button').filter((button) => button.text().trim() === 'Concluir prazo'),
        ).toHaveLength(2)

        expect(
            myWork
                .findAll('button')
                .filter((button) => button.text().trim() === 'Concluir compromisso'),
        ).toHaveLength(2)
    })

    it('nao mostra acoes de meu trabalho sem folders.update', async () => {
        const { wrapper } = await mountPage({
            myWork: defaultMyWork(),
        })

        const myWork = wrapper.find('[data-testid="dashboard-my-work"]')

        expect(myWork.exists()).toBe(true)

        expect(
            myWork.findAll('button').some((button) => button.text().trim() === 'Concluir tarefa'),
        ).toBe(false)

        expect(
            myWork.findAll('button').some((button) => button.text().trim() === 'Concluir prazo'),
        ).toBe(false)

        expect(
            myWork
                .findAll('button')
                .some((button) => button.text().trim() === 'Concluir compromisso'),
        ).toBe(false)
    })

    it('conclui minha tarefa e recarrega dashboard', async () => {
        const { wrapper, folderTasksStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            myWork: defaultMyWork(),
        })

        const completeSpy = vi.spyOn(folderTasksStore, 'completeTask').mockResolvedValue({
            id: 801,
            status: 'completed',
        })

        const button = wrapper.find('[data-testid="dashboard-my-work-task-complete-801"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(completeSpy).toHaveBeenCalledWith(10, 801)
        })

        await vi.waitFor(() => {
            expect(fetchDashboardSpy).toHaveBeenCalledTimes(2)
        })
    })

    it('conclui meu prazo e recarrega dashboard', async () => {
        const { wrapper, folderDeadlinesStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            myWork: defaultMyWork(),
        })

        const completeSpy = vi.spyOn(folderDeadlinesStore, 'completeDeadline').mockResolvedValue({
            id: 803,
            status: 'completed',
        })

        const button = wrapper.find('[data-testid="dashboard-my-work-deadline-complete-803"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(completeSpy).toHaveBeenCalledWith(12, 803)
        })

        await vi.waitFor(() => {
            expect(fetchDashboardSpy).toHaveBeenCalledTimes(2)
        })
    })

    it('conclui meu compromisso e recarrega dashboard', async () => {
        const { wrapper, folderEventsStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            myWork: defaultMyWork(),
        })

        const completeSpy = vi.spyOn(folderEventsStore, 'completeEvent').mockResolvedValue({
            id: 805,
            status: 'completed',
        })

        const button = wrapper.find('[data-testid="dashboard-my-work-event-complete-805"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(completeSpy).toHaveBeenCalledWith(14, 805)
        })

        await vi.waitFor(() => {
            expect(fetchDashboardSpy).toHaveBeenCalledTimes(2)
        })
    })

    it('exibe erro quando conclusao de minha tarefa falha', async () => {
        const { wrapper, folderTasksStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            myWork: defaultMyWork(),
        })

        vi.spyOn(folderTasksStore, 'completeTask').mockRejectedValue(new Error('Falha ao concluir'))

        const button = wrapper.find('[data-testid="dashboard-my-work-task-complete-801"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível concluir a tarefa. Tente novamente.')
        })

        expect(fetchDashboardSpy).toHaveBeenCalledTimes(1)
    })

    it('exibe erro quando conclusao de meu prazo falha', async () => {
        const { wrapper, folderDeadlinesStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            myWork: defaultMyWork(),
        })

        vi.spyOn(folderDeadlinesStore, 'completeDeadline').mockRejectedValue(
            new Error('Falha ao concluir'),
        )

        const button = wrapper.find('[data-testid="dashboard-my-work-deadline-complete-803"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível concluir o prazo. Tente novamente.')
        })

        expect(fetchDashboardSpy).toHaveBeenCalledTimes(1)
    })

    it('exibe erro quando conclusao de meu compromisso falha', async () => {
        const { wrapper, folderEventsStore, fetchDashboardSpy } = await mountPage({
            permissions: ['folders.update'],

            myWork: defaultMyWork(),
        })

        vi.spyOn(folderEventsStore, 'completeEvent').mockRejectedValue(
            new Error('Falha ao concluir'),
        )

        const button = wrapper.find('[data-testid="dashboard-my-work-event-complete-805"]')

        expect(button.exists()).toBe(true)

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível concluir o compromisso. Tente novamente.',
            )
        })

        expect(fetchDashboardSpy).toHaveBeenCalledTimes(1)
    })

    /*
    |--------------------------------------------------------------------------
    | Arquitetura visual do Dashboard
    |--------------------------------------------------------------------------
    */

    it('organiza o dashboard em uma area principal de trabalho', async () => {
        const { wrapper } = await mountPage()

        const workspace = wrapper.find('[data-testid="dashboard-workspace"]')

        expect(workspace.exists()).toBe(true)
    })

    it('apresenta agenda de hoje como area propria do dashboard', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.find('[data-testid="dashboard-today-agenda"]').exists()).toBe(true)
    })

    it('mantem meu trabalho como area propria da nova composicao', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.find('[data-testid="dashboard-my-work"]').exists()).toBe(true)
    })

    it('agrupa atividade e pastas recentes na area secundaria', async () => {
        const { wrapper } = await mountPage()

        const secondary = wrapper.find('[data-testid="dashboard-secondary"]')

        expect(secondary.exists()).toBe(true)

        expect(secondary.text()).toContain('Atividade recente')

        expect(secondary.text()).toContain('Pastas recentes')
    })

    it('apresenta indicadores institucionais identificaveis', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.find('[data-testid="dashboard-stat-clients"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="dashboard-stat-folders"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="dashboard-stat-members"]').exists()).toBe(true)
    })

    it('apresenta prioridades juridicas e agenda em composicao conjunta', async () => {
        const { wrapper } = await mountPage()

        const priorities = wrapper.find('[data-testid="dashboard-priorities-layout"]')

        expect(priorities.exists()).toBe(true)

        expect(priorities.find('[data-testid="dashboard-legal-alerts"]').exists()).toBe(true)

        expect(priorities.find('[data-testid="dashboard-today-agenda"]').exists()).toBe(true)
    })

    it('apresenta movimento do escritorio como area secundaria', async () => {
        const { wrapper } = await mountPage()

        const movement = wrapper.find('[data-testid="dashboard-office-movement"]')

        expect(movement.exists()).toBe(true)

        expect(movement.text()).toContain('Atividade recente')

        expect(movement.text()).toContain('Pastas recentes')
    })

    it('renderiza tarefa de hoje na agenda consolidada', async () => {
        const { wrapper } = await mountPage({
            todayAgenda: [
                {
                    kind: 'task',
                    id: 901,
                    title: 'Revisar contestação',
                    scheduled_at: '2026-08-24T15:00:00.000000Z',
                    priority: 'high',

                    folder: {
                        id: 10,
                        name: 'Ação indenizatória',
                        process_number: '5000000-00.2026.8.21.0001',
                    },
                },
            ],
        })

        const agenda = wrapper.get('[data-testid="dashboard-today-agenda"]')

        expect(agenda.text()).toContain('Revisar contestação')

        expect(agenda.text()).toContain('Tarefa')

        expect(agenda.text()).toContain('Ação indenizatória')
    })

    it('renderiza prazo de hoje na agenda consolidada', async () => {
        const { wrapper } = await mountPage({
            todayAgenda: [
                {
                    kind: 'deadline',
                    id: 902,
                    title: 'Protocolar manifestação',
                    scheduled_at: '2026-08-24T18:00:00.000000Z',

                    folder: {
                        id: 11,
                        name: 'Ação revisional',
                        process_number: '5002222-33.2026.8.21.0022',
                    },
                },
            ],
        })

        const agenda = wrapper.get('[data-testid="dashboard-today-agenda"]')

        expect(agenda.text()).toContain('Protocolar manifestação')

        expect(agenda.text()).toContain('Prazo')

        expect(agenda.text()).toContain('Ação revisional')
    })

    it('renderiza compromisso de hoje na agenda consolidada', async () => {
        const { wrapper } = await mountPage({
            todayAgenda: [
                {
                    kind: 'event',
                    id: 903,
                    title: 'Audiência de instrução',
                    scheduled_at: '2026-08-24T19:00:00.000000Z',
                    type: 'hearing',
                    location: 'Fórum de Pelotas',

                    folder: {
                        id: 12,
                        name: 'Ação de alimentos',
                        process_number: '5003333-44.2026.8.21.0022',
                    },
                },
            ],
        })

        const agenda = wrapper.get('[data-testid="dashboard-today-agenda"]')

        expect(agenda.text()).toContain('Audiência de instrução')

        expect(agenda.text()).toContain('Audiência')

        expect(agenda.text()).toContain('Fórum de Pelotas')
    })

    it('renderiza estado vazio quando agenda consolidada do dia esta vazia', async () => {
        const { wrapper } = await mountPage({
            attention: {
                overdue_tasks: [],
                overdue_deadlines: [],
            },

            todayAgenda: [],
        })

        const agenda = wrapper.get('[data-testid="dashboard-today-agenda"]')

        expect(agenda.text()).toContain('Nenhum item restante para hoje.')
    })
})
