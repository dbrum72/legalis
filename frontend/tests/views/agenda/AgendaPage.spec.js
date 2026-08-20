import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import { createMemoryHistory, createRouter } from 'vue-router'

import AgendaPage from '@/views/agenda/AgendaPage.vue'

import { useAgendaStore } from '@/stores/agenda.js'
import { useAuthStore } from '@/stores/auth.js'
import { useFolderTasksStore } from '@/stores/folder-tasks.js'
import { useFolderDeadlinesStore } from '@/stores/folder-deadlines.js'
import { useFolderEventsStore } from '@/stores/folder-events.js'

function defaultItems() {
    return [
        {
            type: 'task',
            id: 101,
            title: 'Revisar documentos',
            starts_at: '2026-08-20T09:00:00.000000Z',
            ends_at: null,
            priority: 'high',
            event_type: null,
            location: null,
            status: 'pending',
            completed_at: null,

            folder: {
                id: 10,
                name: 'Ação indenizatória',
                process_number: '5000000-00.2026.8.21.0001',
            },
        },

        {
            type: 'deadline',
            id: 201,
            title: 'Protocolar manifestação',
            starts_at: '2026-08-20T15:00:00.000000Z',
            ends_at: null,
            priority: null,
            event_type: null,
            location: null,
            status: 'pending',
            completed_at: null,

            folder: {
                id: 11,
                name: 'Ação de cobrança',
                process_number: null,
            },
        },

        {
            type: 'event',
            id: 301,
            title: 'Audiência de instrução',
            starts_at: '2026-08-21T14:00:00.000000Z',
            ends_at: '2026-08-21T15:00:00.000000Z',
            priority: null,
            event_type: 'hearing',
            location: 'Fórum de Pelotas',
            status: 'scheduled',
            completed_at: null,

            folder: {
                id: 12,
                name: 'Ação revisional',
                process_number: '5002222-33.2026.8.21.0022',
            },
        },
    ]
}

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/agenda',

                name: 'agenda',

                component: {
                    template: '<div>Agenda</div>',
                },
            },

            {
                path: '/folders/:id',

                name: 'folders.show',

                component: {
                    template: '<div>Pasta</div>',
                },
            },
        ],
    })
}

async function mountPage({
    period = {
        start: null,
        end: null,
    },

    items = [],

    permissions = [],
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()

    authStore.permissions = permissions

    const agendaStore = useAgendaStore()

    agendaStore.period = period

    agendaStore.items = items

    const folderTasksStore = useFolderTasksStore()

    const folderDeadlinesStore = useFolderDeadlinesStore()

    const folderEventsStore = useFolderEventsStore()

    const fetchAgendaSpy = vi.spyOn(agendaStore, 'fetchAgenda').mockResolvedValue({
        period: agendaStore.period,

        items: agendaStore.items,
    })

    const completeTaskSpy = vi.spyOn(folderTasksStore, 'completeTask').mockResolvedValue({
        id: 101,
        status: 'completed',
    })

    const completeDeadlineSpy = vi
        .spyOn(folderDeadlinesStore, 'completeDeadline')
        .mockResolvedValue({
            id: 201,
            status: 'completed',
        })

    const completeEventSpy = vi.spyOn(folderEventsStore, 'completeEvent').mockResolvedValue({
        id: 301,
        status: 'completed',
    })

    const router = createTestRouter()

    await router.push('/agenda')

    await router.isReady()

    const wrapper = mount(AgendaPage, {
        global: {
            plugins: [pinia, router],
        },
    })

    return {
        wrapper,

        router,

        authStore,

        agendaStore,

        folderTasksStore,

        folderDeadlinesStore,

        folderEventsStore,

        fetchAgendaSpy,

        completeTaskSpy,

        completeDeadlineSpy,

        completeEventSpy,
    }
}

describe('AgendaPage', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    /*
    |--------------------------------------------------------------------------
    | Estrutura básica
    |--------------------------------------------------------------------------
    */

    it('renderiza titulo e descricao da pagina', async () => {
        const { wrapper } = await mountPage()

        await flushPromises()

        expect(wrapper.text()).toContain('Agenda')

        expect(wrapper.text()).toContain(
            'Acompanhe tarefas, prazos e compromissos em uma visão de calendário.',
        )
    })

    /*
    |--------------------------------------------------------------------------
    | Carregamento
    |--------------------------------------------------------------------------
    */

    it('carrega agenda ao montar', async () => {
        const { fetchAgendaSpy } = await mountPage()

        await flushPromises()

        expect(fetchAgendaSpy).toHaveBeenCalledTimes(1)
    })

    it('carrega intervalo completo do mes atual', async () => {
        const { fetchAgendaSpy } = await mountPage()

        await flushPromises()

        expect(fetchAgendaSpy).toHaveBeenCalledWith({
            start: '2026-08-01',

            end: '2026-08-31',
        })
    })

    /*
    |--------------------------------------------------------------------------
    | Cabeçalho
    |--------------------------------------------------------------------------
    */

    it('renderiza nome do mes atual', async () => {
        const { wrapper } = await mountPage()

        await flushPromises()

        expect(wrapper.text()).toContain('Agosto de 2026')
    })

    it('renderiza os dias da semana', async () => {
        const { wrapper } = await mountPage()

        await flushPromises()

        const text = wrapper.text()

        expect(text).toContain('Seg')
        expect(text).toContain('Ter')
        expect(text).toContain('Qua')
        expect(text).toContain('Qui')
        expect(text).toContain('Sex')
        expect(text).toContain('Sáb')
        expect(text).toContain('Dom')
    })

    /*
    |--------------------------------------------------------------------------
    | Navegação mensal
    |--------------------------------------------------------------------------
    */

    it('navega para o mes anterior e recarrega agenda', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage()

        await flushPromises()

        await wrapper.get('[data-testid="agenda-previous-month"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain('Julho de 2026')

        expect(fetchAgendaSpy).toHaveBeenLastCalledWith({
            start: '2026-07-01',

            end: '2026-07-31',
        })
    })

    it('navega para o proximo mes e recarrega agenda', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage()

        await flushPromises()

        await wrapper.get('[data-testid="agenda-next-month"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain('Setembro de 2026')

        expect(fetchAgendaSpy).toHaveBeenLastCalledWith({
            start: '2026-09-01',

            end: '2026-09-30',
        })
    })

    it('botao hoje retorna para o mes atual', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage()

        await flushPromises()

        await wrapper.get('[data-testid="agenda-previous-month"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain('Julho de 2026')

        await wrapper.get('[data-testid="agenda-today"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain('Agosto de 2026')

        expect(fetchAgendaSpy).toHaveBeenLastCalledWith({
            start: '2026-08-01',

            end: '2026-08-31',
        })
    })

    /*
    |--------------------------------------------------------------------------
    | Grade mensal
    |--------------------------------------------------------------------------
    */

    it('renderiza grade mensal com quarenta e duas celulas', async () => {
        const { wrapper } = await mountPage()

        await flushPromises()

        expect(wrapper.findAll('[data-testid="agenda-day"]')).toHaveLength(42)
    })

    it('destaca o dia atual', async () => {
        const { wrapper } = await mountPage()

        await flushPromises()

        const today = wrapper.find('[data-testid="agenda-day"][data-date="2026-08-20"]')

        expect(today.exists()).toBe(true)

        expect(today.classes()).toContain('agenda-calendar__day--today')
    })

    /*
    |--------------------------------------------------------------------------
    | Itens no calendário
    |--------------------------------------------------------------------------
    */

    it('renderiza tarefa no respectivo dia do calendario', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const day = wrapper.get('[data-testid="agenda-day"][data-date="2026-08-20"]')

        const item = day.find('[data-testid="agenda-calendar-item-task-101"]')

        expect(item.exists()).toBe(true)

        expect(item.text()).toContain('Revisar documentos')

        expect(item.text()).toContain('Tarefa')
    })

    it('renderiza prazo no respectivo dia do calendario', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const day = wrapper.get('[data-testid="agenda-day"][data-date="2026-08-20"]')

        const item = day.find('[data-testid="agenda-calendar-item-deadline-201"]')

        expect(item.exists()).toBe(true)

        expect(item.text()).toContain('Protocolar manifestação')

        expect(item.text()).toContain('Prazo')
    })

    it('renderiza compromisso no respectivo dia do calendario', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const day = wrapper.get('[data-testid="agenda-day"][data-date="2026-08-21"]')

        const item = day.find('[data-testid="agenda-calendar-item-event-301"]')

        expect(item.exists()).toBe(true)

        expect(item.text()).toContain('Audiência de instrução')

        expect(item.text()).toContain('Compromisso')
    })

    it('nao renderiza item em dia diferente de sua data', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const august20 = wrapper.get('[data-testid="agenda-day"][data-date="2026-08-20"]')

        const august21 = wrapper.get('[data-testid="agenda-day"][data-date="2026-08-21"]')

        expect(august20.find('[data-testid="agenda-calendar-item-event-301"]').exists()).toBe(false)

        expect(august21.find('[data-testid="agenda-calendar-item-task-101"]').exists()).toBe(false)
    })

    /*
    |--------------------------------------------------------------------------
    | Dia selecionado
    |--------------------------------------------------------------------------
    */

    it('seleciona hoje por padrao', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const panel = wrapper.get('[data-testid="agenda-selected-day"]')

        expect(panel.text()).toContain('20 de agosto de 2026')
    })

    it('renderiza somente itens do dia selecionado', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const panel = wrapper.get('[data-testid="agenda-selected-day"]')

        expect(panel.text()).toContain('Revisar documentos')

        expect(panel.text()).toContain('Protocolar manifestação')

        expect(panel.text()).not.toContain('Audiência de instrução')
    })

    it('altera painel ao selecionar outro dia', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-day"][data-date="2026-08-21"]').trigger('click')

        await flushPromises()

        const panel = wrapper.get('[data-testid="agenda-selected-day"]')

        expect(panel.text()).toContain('21 de agosto de 2026')

        expect(panel.text()).toContain('Audiência de instrução')

        expect(panel.text()).not.toContain('Revisar documentos')
    })

    it('renderiza dados da pasta no painel do dia', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const panel = wrapper.get('[data-testid="agenda-selected-day"]')

        expect(panel.text()).toContain('Ação indenizatória')

        expect(panel.text()).toContain('5000000-00.2026.8.21.0001')

        expect(panel.text()).toContain('Ação de cobrança')
    })

    it('renderiza tipo dos itens no painel do dia', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const panel = wrapper.get('[data-testid="agenda-selected-day"]')

        expect(panel.get('[data-testid="agenda-selected-item-task-101"]').text()).toContain(
            'Tarefa',
        )

        expect(panel.get('[data-testid="agenda-selected-item-deadline-201"]').text()).toContain(
            'Prazo',
        )
    })

    it('renderiza local do compromisso no painel do dia', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-day"][data-date="2026-08-21"]').trigger('click')

        await flushPromises()

        expect(wrapper.get('[data-testid="agenda-selected-day"]').text()).toContain(
            'Fórum de Pelotas',
        )
    })

    it('renderiza estado vazio quando dia selecionado nao possui itens', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-day"][data-date="2026-08-22"]').trigger('click')

        await flushPromises()

        expect(wrapper.get('[data-testid="agenda-selected-day"]').text()).toContain(
            'Nenhum item agendado para este dia.',
        )
    })

    /*
    |--------------------------------------------------------------------------
    | Navegação para pasta
    |--------------------------------------------------------------------------
    */

    it('navega para pasta a partir de tarefa', async () => {
        const { wrapper, router } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const button = wrapper.get('[data-testid="agenda-selected-folder-task-101"]')

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.show')

        expect(router.currentRoute.value.params.id).toBe('10')
    })

    it('navega para pasta a partir de prazo', async () => {
        const { wrapper, router } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const button = wrapper.get('[data-testid="agenda-selected-folder-deadline-201"]')

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.show')

        expect(router.currentRoute.value.params.id).toBe('11')
    })

    /*
    |--------------------------------------------------------------------------
    | Permissão de conclusão
    |--------------------------------------------------------------------------
    */

    it('mostra acoes de conclusao com folders.update', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),

            permissions: ['folders.update'],
        })

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-complete-task-101"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-complete-deadline-201"]').exists()).toBe(true)

        await wrapper.get('[data-testid="agenda-day"][data-date="2026-08-21"]').trigger('click')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-complete-event-301"]').exists()).toBe(true)
    })

    it('nao mostra acoes de conclusao sem folders.update', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),

            permissions: [],
        })

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-complete-task-101"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-complete-deadline-201"]').exists()).toBe(false)

        await wrapper.get('[data-testid="agenda-day"][data-date="2026-08-21"]').trigger('click')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-complete-event-301"]').exists()).toBe(false)
    })

    /*
    |--------------------------------------------------------------------------
    | Conclusão
    |--------------------------------------------------------------------------
    */

    it('conclui tarefa e recarrega agenda', async () => {
        const { wrapper, completeTaskSpy, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),

            permissions: ['folders.update'],
        })

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-complete-task-101"]').trigger('click')

        await flushPromises()

        expect(completeTaskSpy).toHaveBeenCalledWith(10, 101)

        expect(fetchAgendaSpy).toHaveBeenCalledWith({
            start: '2026-08-01',

            end: '2026-08-31',
        })
    })

    it('conclui prazo e recarrega agenda', async () => {
        const { wrapper, completeDeadlineSpy, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),

            permissions: ['folders.update'],
        })

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-complete-deadline-201"]').trigger('click')

        await flushPromises()

        expect(completeDeadlineSpy).toHaveBeenCalledWith(11, 201)

        expect(fetchAgendaSpy).toHaveBeenCalledWith({
            start: '2026-08-01',

            end: '2026-08-31',
        })
    })

    it('conclui compromisso e recarrega agenda', async () => {
        const { wrapper, completeEventSpy, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),

            permissions: ['folders.update'],
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-day"][data-date="2026-08-21"]').trigger('click')

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-complete-event-301"]').trigger('click')

        await flushPromises()

        expect(completeEventSpy).toHaveBeenCalledWith(12, 301)

        expect(fetchAgendaSpy).toHaveBeenCalledWith({
            start: '2026-08-01',

            end: '2026-08-31',
        })
    })

    /*
    |--------------------------------------------------------------------------
    | Erros de conclusão
    |--------------------------------------------------------------------------
    */

    it('exibe erro quando conclusao de tarefa falha', async () => {
        const { wrapper, completeTaskSpy } = await mountPage({
            items: defaultItems(),

            permissions: ['folders.update'],
        })

        await flushPromises()

        completeTaskSpy.mockRejectedValue(new Error('Falha ao concluir tarefa'))

        await wrapper.get('[data-testid="agenda-complete-task-101"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain('Não foi possível concluir a tarefa. Tente novamente.')
    })

    it('exibe erro quando conclusao de prazo falha', async () => {
        const { wrapper, completeDeadlineSpy } = await mountPage({
            items: defaultItems(),

            permissions: ['folders.update'],
        })

        await flushPromises()

        completeDeadlineSpy.mockRejectedValue(new Error('Falha ao concluir prazo'))

        await wrapper.get('[data-testid="agenda-complete-deadline-201"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain('Não foi possível concluir o prazo. Tente novamente.')
    })

    it('exibe erro quando conclusao de compromisso falha', async () => {
        const { wrapper, completeEventSpy } = await mountPage({
            items: defaultItems(),

            permissions: ['folders.update'],
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-day"][data-date="2026-08-21"]').trigger('click')

        await flushPromises()

        completeEventSpy.mockRejectedValue(new Error('Falha ao concluir compromisso'))

        await wrapper.get('[data-testid="agenda-complete-event-301"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain(
            'Não foi possível concluir o compromisso. Tente novamente.',
        )
    })

    /*
    |--------------------------------------------------------------------------
    | Erro de carregamento
    |--------------------------------------------------------------------------
    */

    it('exibe erro quando carregamento da agenda falha', async () => {
        const pinia = createPinia()

        setActivePinia(pinia)

        const agendaStore = useAgendaStore()

        vi.spyOn(agendaStore, 'fetchAgenda').mockRejectedValue(new Error('Falha ao carregar'))

        const router = createTestRouter()

        await router.push('/agenda')

        await router.isReady()

        const wrapper = mount(AgendaPage, {
            global: {
                plugins: [pinia, router],
            },
        })

        await flushPromises()

        expect(wrapper.text()).toContain('Não foi possível carregar a agenda. Tente novamente.')
    })

    it('inicia na visualizacao mensal', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-list"]').exists()).toBe(false)
    })

    it('alterna da visualizacao mensal para lista', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-list"]').trigger('click')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-list"]').exists()).toBe(true)
    })

    it('retorna da visualizacao em lista para o calendario', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-list"]').trigger('click')

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-month"]').trigger('click')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-list"]').exists()).toBe(false)
    })

    it('renderiza itens cronologicamente na visualizacao em lista', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-list"]').trigger('click')

        await flushPromises()

        const items = wrapper.findAll('[data-testid^="agenda-list-item-"]')

        expect(items).toHaveLength(3)

        expect(items[0].text()).toContain('Revisar documentos')

        expect(items[1].text()).toContain('Protocolar manifestação')

        expect(items[2].text()).toContain('Audiência de instrução')
    })

    it('agrupa itens da lista pela data', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-list"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain('20 de agosto de 2026')

        expect(wrapper.text()).toContain('21 de agosto de 2026')
    })

    it('renderiza tipo dos itens na visualizacao em lista', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-list"]').trigger('click')

        await flushPromises()

        expect(wrapper.get('[data-testid="agenda-list-item-task-101"]').text()).toContain('Tarefa')

        expect(wrapper.get('[data-testid="agenda-list-item-deadline-201"]').text()).toContain(
            'Prazo',
        )

        expect(wrapper.get('[data-testid="agenda-list-item-event-301"]').text()).toContain(
            'Compromisso',
        )
    })

    it('renderiza pasta relacionada na visualizacao em lista', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-list"]').trigger('click')

        await flushPromises()

        const item = wrapper.get('[data-testid="agenda-list-item-task-101"]')

        expect(item.text()).toContain('Ação indenizatória')

        expect(item.text()).toContain('5000000-00.2026.8.21.0001')
    })

    it('exibe estado vazio na visualizacao em lista', async () => {
        const { wrapper } = await mountPage({
            items: [],
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-list"]').trigger('click')

        await flushPromises()

        expect(wrapper.text()).toContain('Nenhum item encontrado neste período.')
    })

    /*
    |--------------------------------------------------------------------------
    | Filtro por tipo
    |--------------------------------------------------------------------------
    */

    it('renderiza filtro de tipo como select e inicia em Todos', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const label = wrapper.get('label[for="agenda-filter-type"]')

        const select = wrapper.get('[data-testid="agenda-filter-type"]')

        expect(label.text()).toContain('Tipo')

        expect(select.element.tagName).toBe('SELECT')

        expect(select.element.value).toBe('all')
    })

    it('filtra apenas prazos pelo select de tipo', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-type"]').setValue('deadline')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-deadline-201"]').exists()).toBe(
            true,
        )

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-101"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-calendar-item-event-301"]').exists()).toBe(false)
    })

    it('filtra apenas tarefas pelo select de tipo', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-type"]').setValue('task')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-101"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-deadline-201"]').exists()).toBe(
            false,
        )

        expect(wrapper.find('[data-testid="agenda-calendar-item-event-301"]').exists()).toBe(false)
    })

    it('filtra apenas compromissos pelo select de tipo', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-type"]').setValue('event')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-event-301"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-101"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-calendar-item-deadline-201"]').exists()).toBe(
            false,
        )
    })

    it('aplica filtro de tipo tambem na visualizacao em lista', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-type"]').setValue('deadline')

        await wrapper.get('[data-testid="agenda-view-list"]').trigger('click')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-list-item-deadline-201"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-list-item-task-101"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-list-item-event-301"]').exists()).toBe(false)
    })

    it('filtro de tipo nao realiza nova chamada a api', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-filter-type"]').setValue('task')

        await flushPromises()

        expect(fetchAgendaSpy).not.toHaveBeenCalled()
    })

    it('retorna para todos os tipos pelo select', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const select = wrapper.get('[data-testid="agenda-filter-type"]')

        await select.setValue('task')

        await flushPromises()

        await select.setValue('all')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-101"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-deadline-201"]').exists()).toBe(
            true,
        )

        expect(wrapper.find('[data-testid="agenda-calendar-item-event-301"]').exists()).toBe(true)
    })

    /*
    |--------------------------------------------------------------------------
    | Filtro por situação
    |--------------------------------------------------------------------------
    */

    it('renderiza filtro de situacao como select e inicia em Todas', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const label = wrapper.get('label[for="agenda-filter-status"]')

        const select = wrapper.get('[data-testid="agenda-filter-status"]')

        expect(label.text()).toContain('Situação')

        expect(select.element.tagName).toBe('SELECT')

        expect(select.element.value).toBe('all')
    })

    it('filtra itens pendentes e futuros pelo select de situacao', async () => {
        const items = [
            {
                type: 'task',
                id: 401,
                title: 'Tarefa pendente futura',
                starts_at: '2026-08-21T14:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            },

            {
                type: 'deadline',
                id: 402,
                title: 'Prazo vencido',
                starts_at: '2026-08-19T14:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 11,
                    name: 'Pasta B',
                    process_number: null,
                },
            },

            {
                type: 'event',
                id: 403,
                title: 'Compromisso futuro',
                starts_at: '2026-08-22T14:00:00.000000Z',
                status: 'scheduled',
                completed_at: null,

                folder: {
                    id: 12,
                    name: 'Pasta C',
                    process_number: null,
                },
            },

            {
                type: 'task',
                id: 404,
                title: 'Tarefa concluída',
                starts_at: '2026-08-18T14:00:00.000000Z',
                status: 'completed',
                completed_at: '2026-08-18T15:00:00.000000Z',

                folder: {
                    id: 13,
                    name: 'Pasta D',
                    process_number: null,
                },
            },
        ]

        const { wrapper } = await mountPage({
            items,
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-status"]').setValue('pending')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-401"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-event-403"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-deadline-402"]').exists()).toBe(
            false,
        )

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-404"]').exists()).toBe(false)
    })

    it('filtra itens concluidos pelo select de situacao', async () => {
        const items = [
            {
                type: 'task',
                id: 411,
                title: 'Tarefa concluída',
                starts_at: '2026-08-18T14:00:00.000000Z',
                status: 'completed',
                completed_at: '2026-08-18T15:00:00.000000Z',

                folder: {
                    id: 20,
                    name: 'Pasta A',
                    process_number: null,
                },
            },

            {
                type: 'deadline',
                id: 412,
                title: 'Prazo concluído',
                starts_at: '2026-08-19T14:00:00.000000Z',
                status: 'completed',
                completed_at: '2026-08-19T15:00:00.000000Z',

                folder: {
                    id: 21,
                    name: 'Pasta B',
                    process_number: null,
                },
            },

            {
                type: 'event',
                id: 413,
                title: 'Compromisso pendente',
                starts_at: '2026-08-22T14:00:00.000000Z',
                status: 'scheduled',
                completed_at: null,

                folder: {
                    id: 22,
                    name: 'Pasta C',
                    process_number: null,
                },
            },
        ]

        const { wrapper } = await mountPage({
            items,
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-status"]').setValue('completed')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-411"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-deadline-412"]').exists()).toBe(
            true,
        )

        expect(wrapper.find('[data-testid="agenda-calendar-item-event-413"]').exists()).toBe(false)
    })

    it('filtra tarefas e prazos vencidos pelo select de situacao', async () => {
        const items = [
            {
                type: 'task',
                id: 421,
                title: 'Tarefa vencida',
                starts_at: '2026-08-19T09:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 30,
                    name: 'Pasta A',
                    process_number: null,
                },
            },

            {
                type: 'deadline',
                id: 422,
                title: 'Prazo vencido',
                starts_at: '2026-08-19T10:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 31,
                    name: 'Pasta B',
                    process_number: null,
                },
            },

            {
                type: 'task',
                id: 423,
                title: 'Tarefa futura',
                starts_at: '2026-08-21T09:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 32,
                    name: 'Pasta C',
                    process_number: null,
                },
            },
        ]

        const { wrapper } = await mountPage({
            items,
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-status"]').setValue('overdue')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-421"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-deadline-422"]').exists()).toBe(
            true,
        )

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-423"]').exists()).toBe(false)
    })

    it('considera compromisso agendado passado como vencido pelo select', async () => {
        const items = [
            {
                type: 'event',
                id: 431,
                title: 'Compromisso passado',
                starts_at: '2026-08-19T14:00:00.000000Z',
                status: 'scheduled',
                completed_at: null,

                folder: {
                    id: 40,
                    name: 'Pasta A',
                    process_number: null,
                },
            },

            {
                type: 'event',
                id: 432,
                title: 'Compromisso futuro',
                starts_at: '2026-08-21T14:00:00.000000Z',
                status: 'scheduled',
                completed_at: null,

                folder: {
                    id: 41,
                    name: 'Pasta B',
                    process_number: null,
                },
            },
        ]

        const { wrapper } = await mountPage({
            items,
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-status"]').setValue('overdue')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-event-431"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-event-432"]').exists()).toBe(false)
    })

    it('combina selects de tipo e situacao', async () => {
        const items = [
            {
                type: 'task',
                id: 441,
                title: 'Tarefa vencida',
                starts_at: '2026-08-19T09:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 50,
                    name: 'Pasta A',
                    process_number: null,
                },
            },

            {
                type: 'deadline',
                id: 442,
                title: 'Prazo vencido',
                starts_at: '2026-08-19T10:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 51,
                    name: 'Pasta B',
                    process_number: null,
                },
            },
        ]

        const { wrapper } = await mountPage({
            items,
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-filter-type"]').setValue('task')

        await wrapper.get('[data-testid="agenda-filter-status"]').setValue('overdue')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-441"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-deadline-442"]').exists()).toBe(
            false,
        )
    })

    it('filtro de situacao nao realiza nova chamada a api', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-filter-status"]').setValue('pending')

        await flushPromises()

        expect(fetchAgendaSpy).not.toHaveBeenCalled()
    })

    it('retorna para todas as situacoes pelo select', async () => {
        const items = [
            {
                type: 'task',
                id: 451,
                title: 'Tarefa vencida',
                starts_at: '2026-08-19T09:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 60,
                    name: 'Pasta A',
                    process_number: null,
                },
            },

            {
                type: 'task',
                id: 452,
                title: 'Tarefa concluída',
                starts_at: '2026-08-18T09:00:00.000000Z',
                status: 'completed',
                completed_at: '2026-08-18T10:00:00.000000Z',

                folder: {
                    id: 61,
                    name: 'Pasta B',
                    process_number: null,
                },
            },

            {
                type: 'task',
                id: 453,
                title: 'Tarefa futura',
                starts_at: '2026-08-21T09:00:00.000000Z',
                status: 'pending',
                completed_at: null,

                folder: {
                    id: 62,
                    name: 'Pasta C',
                    process_number: null,
                },
            },
        ]

        const { wrapper } = await mountPage({
            items,
        })

        await flushPromises()

        const select = wrapper.get('[data-testid="agenda-filter-status"]')

        await select.setValue('overdue')

        await flushPromises()

        await select.setValue('all')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-451"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-452"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-calendar-item-task-453"]').exists()).toBe(true)
    })

    /*
    |--------------------------------------------------------------------------
    | Visualização semanal
    |--------------------------------------------------------------------------
    */

    it('renderiza opcao de visualizacao semanal', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        const button = wrapper.get('[data-testid="agenda-view-week"]')

        expect(button.text()).toContain('Semana')
    })

    it('alterna da visualizacao mensal para semanal', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-calendar"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-week"]').exists()).toBe(true)
    })

    it('renderiza sete dias na visualizacao semanal', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        expect(wrapper.findAll('[data-testid="agenda-week-day"]')).toHaveLength(7)
    })

    it('semana inicia na segunda-feira e termina no domingo', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        const days = wrapper.findAll('[data-testid="agenda-week-day"]')

        expect(days).toHaveLength(7)

        expect(days[0].attributes('data-date')).toBe('2026-08-17')

        expect(days[6].attributes('data-date')).toBe('2026-08-23')
    })

    it('destaca hoje na visualizacao semanal', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        const today = wrapper.get('[data-testid="agenda-week-day"][data-date="2026-08-20"]')

        expect(today.classes()).toContain('agenda-week__day--today')
    })

    it('renderiza itens no respectivo dia da semana', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        const august20 = wrapper.get('[data-testid="agenda-week-day"][data-date="2026-08-20"]')

        const august21 = wrapper.get('[data-testid="agenda-week-day"][data-date="2026-08-21"]')

        expect(august20.find('[data-testid="agenda-week-item-task-101"]').exists()).toBe(true)

        expect(august20.find('[data-testid="agenda-week-item-deadline-201"]').exists()).toBe(true)

        expect(august20.find('[data-testid="agenda-week-item-event-301"]').exists()).toBe(false)

        expect(august21.find('[data-testid="agenda-week-item-event-301"]').exists()).toBe(true)
    })

    it('ordena itens cronologicamente dentro do dia da semana', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        const day = wrapper.get('[data-testid="agenda-week-day"][data-date="2026-08-20"]')

        const items = day.findAll('[data-testid^="agenda-week-item-"]')

        expect(items).toHaveLength(2)

        expect(items[0].text()).toContain('Revisar documentos')

        expect(items[1].text()).toContain('Protocolar manifestação')
    })

    it('navega para semana anterior e recarrega intervalo semanal', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-previous-month"]').trigger('click')

        await flushPromises()

        expect(fetchAgendaSpy).toHaveBeenCalledWith({
            start: '2026-08-10',

            end: '2026-08-16',
        })
    })

    it('navega para proxima semana e recarrega intervalo semanal', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-next-month"]').trigger('click')

        await flushPromises()

        expect(fetchAgendaSpy).toHaveBeenCalledWith({
            start: '2026-08-24',

            end: '2026-08-30',
        })
    })

    it('botao hoje retorna para semana atual', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        await wrapper.get('[data-testid="agenda-previous-month"]').trigger('click')

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-today"]').trigger('click')

        await flushPromises()

        expect(fetchAgendaSpy).toHaveBeenCalledWith({
            start: '2026-08-17',

            end: '2026-08-23',
        })
    })

    it('aplica filtro de tipo na visualizacao semanal', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await wrapper.get('[data-testid="agenda-filter-type"]').setValue('deadline')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-week-item-deadline-201"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-week-item-task-101"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-week-item-event-301"]').exists()).toBe(false)
    })

    it('aplica filtro de situacao na visualizacao semanal', async () => {
        const items = [
            ...defaultItems(),

            {
                type: 'task',
                id: 501,
                title: 'Tarefa concluída',
                starts_at: '2026-08-19T10:00:00.000000Z',
                ends_at: null,
                priority: null,
                event_type: null,
                location: null,
                status: 'completed',
                completed_at: '2026-08-19T11:00:00.000000Z',

                folder: {
                    id: 20,
                    name: 'Pasta concluída',
                    process_number: null,
                },
            },
        ]

        const { wrapper } = await mountPage({
            items,
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await wrapper.get('[data-testid="agenda-filter-status"]').setValue('completed')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-week-item-task-501"]').exists()).toBe(true)

        expect(wrapper.find('[data-testid="agenda-week-item-task-101"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-week-item-deadline-201"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-week-item-event-301"]').exists()).toBe(false)
    })

    it('alternar para semana nao realiza nova chamada a api', async () => {
        const { wrapper, fetchAgendaSpy } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        fetchAgendaSpy.mockClear()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        expect(fetchAgendaSpy).not.toHaveBeenCalled()
    })

    it('retorna da visualizacao semanal para mensal', async () => {
        const { wrapper } = await mountPage({
            items: defaultItems(),
        })

        await flushPromises()

        await wrapper.get('[data-testid="agenda-view-week"]').trigger('click')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-week"]').exists()).toBe(true)

        await wrapper.get('[data-testid="agenda-view-month"]').trigger('click')

        await flushPromises()

        expect(wrapper.find('[data-testid="agenda-week"]').exists()).toBe(false)

        expect(wrapper.find('[data-testid="agenda-calendar"]').exists()).toBe(true)
    })
})
