import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import FolderShowPage from '@/views/folders/FolderShowPage.vue'
import AppTabs from '@/components/ui/AppTabs/index.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useFoldersStore } from '@/stores/folders.js'

vi.mock('@/api/folders.js', () => ({
    listFolders: vi.fn(),
    getFolder: vi.fn(),
    createFolder: vi.fn(),
    updateFolder: vi.fn(),
    deleteFolder: vi.fn(),
}))

vi.mock('@/api/folder-clients.js', () => ({
    createFolderClient: vi.fn(),
    updateFolderClient: vi.fn(),
    deleteFolderClient: vi.fn(),
}))

function defaultFolder() {
    return {
        id: 10,

        name: 'Processo Maria da Silva',

        process_number: '5001234-56.2026.8.21.0022',

        summary: {
            documents_count: 4,

            pending_tasks_count: 2,

            pending_deadlines_count: 3,

            attention: {
                deadlines: [
                    {
                        id: 201,

                        title: 'Apresentar réplica',

                        description: null,

                        due_at: '2026-08-20T18:00:00.000000Z',

                        status: 'pending',

                        urgency: 'overdue',
                    },

                    {
                        id: 202,

                        title: 'Protocolar manifestação',

                        description: null,

                        due_at: '2026-08-21T20:00:00.000000Z',

                        status: 'pending',

                        urgency: 'today',
                    },

                    {
                        id: 203,

                        title: 'Juntar documentos',

                        description: null,

                        due_at: '2026-08-23T18:00:00.000000Z',

                        status: 'pending',

                        urgency: 'upcoming',
                    },
                ],

                tasks: [
                    {
                        id: 301,

                        title: 'Revisar contestação',

                        description: null,

                        priority: 'high',

                        due_at: '2026-08-20T18:00:00.000000Z',

                        status: 'pending',

                        urgency: 'overdue',
                    },

                    {
                        id: 302,

                        title: 'Conferir documentos',

                        description: null,

                        priority: 'medium',

                        due_at: '2026-08-21T20:00:00.000000Z',

                        status: 'pending',

                        urgency: 'today',
                    },

                    {
                        id: 303,

                        title: 'Contatar cliente',

                        description: null,

                        priority: 'high',

                        due_at: null,

                        status: 'pending',

                        urgency: 'unscheduled',
                    },
                ],
            },

            next_event: {
                id: 51,

                type: 'hearing',

                title: 'Audiência de instrução',

                starts_at: '2026-09-10T14:00:00.000000Z',

                ends_at: '2026-09-10T15:30:00.000000Z',

                location: '3ª Vara Cível de Pelotas',
            },

            latest_movement: {
                id: 61,

                occurred_at: '2026-09-08T13:00:00.000000Z',

                title: 'Despacho publicado',

                description: 'Juízo determinou manifestação da parte autora.',
            },
        },

        folder_clients: [
            {
                id: 101,

                folder_id: 10,

                client_id: 20,

                qualification_id: 1,

                client: {
                    id: 20,

                    name: 'Maria da Silva',

                    document: '12345678901',
                },

                qualification: {
                    id: 1,

                    name: 'Autora',
                },
            },

            {
                id: 102,

                folder_id: 10,

                client_id: 21,

                qualification_id: 2,

                client: {
                    id: 21,

                    name: 'João dos Santos',

                    document: '98765432100',
                },

                qualification: {
                    id: 2,

                    name: 'Réu',
                },
            },
        ],
    }
}

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/folders',

                name: 'folders',

                component: {
                    template: '<div>Pastas</div>',
                },
            },

            {
                path: '/folders/:id',

                name: 'folders.show',

                component: FolderShowPage,
            },

            {
                path: '/folders/:id/edit',

                name: 'folders.edit',

                component: {
                    template: '<div>Editar pasta</div>',
                },
            },
        ],
    })
}

async function mountPage({
    folder = defaultFolder(),

    permissions = [],

    fetchError = null,
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    const authStore = useAuthStore()

    const foldersStore = useFoldersStore()

    authStore.permissions = permissions

    const fetchFolderSpy = vi.spyOn(foldersStore, 'fetchFolder')

    if (fetchError) {
        fetchFolderSpy.mockRejectedValue(fetchError)
    } else {
        fetchFolderSpy.mockImplementation(async (id) => {
            const loadedFolder = {
                ...folder,

                id: Number(id),
            }

            foldersStore.folder = loadedFolder

            return loadedFolder
        })
    }

    await router.push('/folders/10')

    await router.isReady()

    const wrapper = mount(FolderShowPage, {
        global: {
            plugins: [pinia, router],

            stubs: {
                FolderDocuments: {
                    name: 'FolderDocuments',

                    props: ['folderId'],

                    template: '<div data-test="folder-documents" />',
                },

                FolderMovements: {
                    name: 'FolderMovements',

                    props: ['folderId'],

                    template: '<div data-test="folder-movements" />',
                },

                FolderDeadlines: {
                    name: 'FolderDeadlines',

                    props: ['folderId'],

                    template: '<div data-test="folder-deadlines" />',
                },

                FolderEvents: {
                    name: 'FolderEvents',

                    props: ['folderId'],

                    emits: ['changed'],

                    template: '<div data-test="folder-events" />',
                },

                FolderTasks: {
                    name: 'FolderTasks',

                    props: ['folderId'],

                    template: '<div data-test="folder-tasks" />',
                },
            },
        },
    })

    await flushPromises()

    return {
        wrapper,
        router,
        authStore,
        foldersStore,
        fetchFolderSpy,
    }
}

function findButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

describe('FolderShowPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('carrega pasta pelo id da rota ao montar', async () => {
        const { fetchFolderSpy } = await mountPage()

        expect(fetchFolderSpy).toHaveBeenCalledTimes(1)

        expect(fetchFolderSpy).toHaveBeenCalledWith(10)
    })

    it('renderiza título e descrição da página', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Detalhes da pasta')

        expect(wrapper.text()).toContain('Consulte os dados da pasta jurídica e suas partes.')
    })

    it('renderiza dados gerais da pasta', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Processo Maria da Silva')

        expect(wrapper.text()).toContain('5001234-56.2026.8.21.0022')
    })

    it('renderiza número do processo ausente com travessão', async () => {
        const folder = defaultFolder()

        folder.process_number = null

        const { wrapper } = await mountPage({
            folder,
        })

        const text = wrapper.text()

        expect(text).toContain('Número do processo')

        expect(text).toContain('—')
    })

    it('renderiza partes vinculadas', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'clients')

        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('Partes')

        expect(wrapper.text()).toContain('Maria da Silva')

        expect(wrapper.text()).toContain('12345678901')

        expect(wrapper.text()).toContain('Autora')

        expect(wrapper.text()).toContain('João dos Santos')

        expect(wrapper.text()).toContain('98765432100')

        expect(wrapper.text()).toContain('Réu')
    })

    it('renderiza estado vazio quando não existem partes', async () => {
        const folder = defaultFolder()

        folder.folder_clients = []

        const { wrapper } = await mountPage({
            folder,
        })

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'clients')

        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('Nenhuma parte vinculada.')
    })

    it('renderiza dados ausentes da parte com travessão', async () => {
        const folder = defaultFolder()

        folder.folder_clients = [
            {
                id: 101,

                folder_id: 10,

                client_id: 20,

                qualification_id: null,

                client: {
                    id: 20,

                    name: 'Maria da Silva',

                    document: null,
                },

                qualification: null,
            },
        ]

        const { wrapper } = await mountPage({
            folder,
        })

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'clients')

        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('Maria da Silva')

        expect(wrapper.text()).toContain('—')
    })

    it('renderiza navegação das seções da pasta', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.exists()).toBe(true)

        expect(tabs.props('items')).toEqual([
            {
                value: 'overview',
                label: 'Visão geral',
            },

            {
                value: 'clients',
                label: 'Partes',
            },

            {
                value: 'documents',
                label: 'Documentos',
            },

            {
                value: 'movements',
                label: 'Movimentações',
            },

            {
                value: 'deadlines',
                label: 'Prazos',
            },

            {
                value: 'events',
                label: 'Agenda',
            },

            {
                value: 'tasks',
                label: 'Tarefas',
            },
        ])
    })

    it('inicia pela seção Visão geral', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.props('modelValue')).toBe('overview')

        expect(wrapper.text()).toContain('Visão operacional')

        expect(wrapper.text()).toContain('Dados gerais')
    })

    it('não monta módulos operacionais enquanto Visão geral está ativa', async () => {
        const { wrapper } = await mountPage()

        expect(
            wrapper
                .findComponent({
                    name: 'FolderDocuments',
                })
                .exists(),
        ).toBe(false)

        expect(
            wrapper
                .findComponent({
                    name: 'FolderMovements',
                })
                .exists(),
        ).toBe(false)

        expect(
            wrapper
                .findComponent({
                    name: 'FolderDeadlines',
                })
                .exists(),
        ).toBe(false)

        expect(
            wrapper
                .findComponent({
                    name: 'FolderEvents',
                })
                .exists(),
        ).toBe(false)

        expect(
            wrapper
                .findComponent({
                    name: 'FolderTasks',
                })
                .exists(),
        ).toBe(false)
    })

    it('exibe Partes ao selecionar a respectiva seção', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'clients')

        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('Partes')

        expect(wrapper.text()).toContain('Maria da Silva')

        expect(wrapper.text()).not.toContain('Visão operacional')
    })

    it('monta Documentos somente quando sua seção é selecionada', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'documents')

        await wrapper.vm.$nextTick()

        const component = wrapper.findComponent({
            name: 'FolderDocuments',
        })

        expect(component.exists()).toBe(true)

        expect(component.props('folderId')).toBe(10)

        expect(
            wrapper
                .findComponent({
                    name: 'FolderMovements',
                })
                .exists(),
        ).toBe(false)
    })

    it('monta Movimentações somente quando sua seção é selecionada', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'movements')

        await wrapper.vm.$nextTick()

        const component = wrapper.findComponent({
            name: 'FolderMovements',
        })

        expect(component.exists()).toBe(true)

        expect(component.props('folderId')).toBe(10)

        expect(
            wrapper
                .findComponent({
                    name: 'FolderDocuments',
                })
                .exists(),
        ).toBe(false)
    })

    it('monta Prazos somente quando sua seção é selecionada', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'deadlines')

        await wrapper.vm.$nextTick()

        const component = wrapper.findComponent({
            name: 'FolderDeadlines',
        })

        expect(component.exists()).toBe(true)

        expect(component.props('folderId')).toBe(10)
    })

    it('monta Agenda somente quando sua seção é selecionada', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'events')

        await wrapper.vm.$nextTick()

        const component = wrapper.findComponent({
            name: 'FolderEvents',
        })

        expect(component.exists()).toBe(true)

        expect(component.props('folderId')).toBe(10)
    })

    it('monta Tarefas somente quando sua seção é selecionada', async () => {
        const { wrapper } = await mountPage()

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'tasks')

        await wrapper.vm.$nextTick()

        const component = wrapper.findComponent({
            name: 'FolderTasks',
        })

        expect(component.exists()).toBe(true)

        expect(component.props('folderId')).toBe(10)
    })

    it('exibe erro quando carregamento da pasta falha', async () => {
        const { wrapper } = await mountPage({
            fetchError: new Error('Falha'),
        })

        expect(wrapper.text()).toContain('Não foi possível carregar a pasta. Tente novamente.')
    })

    it('não mostra Editar sem folders.update', async () => {
        const { wrapper } = await mountPage({
            permissions: [],
        })

        expect(findButton(wrapper, 'Editar')).toBeUndefined()
    })

    it('mostra Editar com folders.update', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.update'],
        })

        expect(findButton(wrapper, 'Editar')).toBeTruthy()
    })

    it('navega para edição da pasta', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['folders.update'],
        })

        const button = findButton(wrapper, 'Editar')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders.edit')

        expect(router.currentRoute.value.params.id).toBe('10')
    })

    it('mostra botão Voltar', async () => {
        const { wrapper } = await mountPage()

        expect(findButton(wrapper, 'Voltar')).toBeTruthy()
    })

    it('Voltar navega para listagem de pastas', async () => {
        const { wrapper, router } = await mountPage()

        const button = findButton(wrapper, 'Voltar')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('folders')
    })

    it('renderiza resumo operacional da pasta', async () => {
        const { wrapper } = await mountPage()

        const text = wrapper.text()

        expect(text).toContain('Visão operacional')

        expect(text).toContain('Documentos')

        expect(text).toContain('4')

        expect(text).toContain('Tarefas pendentes')

        expect(text).toContain('2')

        expect(text).toContain('Prazos pendentes')

        expect(text).toContain('3')
    })

    it('renderiza próximo compromisso no resumo operacional', async () => {
        const { wrapper } = await mountPage()

        const text = wrapper.text()

        expect(text).toContain('Próximo compromisso')

        expect(text).toContain('Audiência de instrução')

        expect(text).toContain('3ª Vara Cível de Pelotas')
    })

    it('renderiza última movimentação no resumo operacional', async () => {
        const { wrapper } = await mountPage()

        const text = wrapper.text()

        expect(text).toContain('Última movimentação')

        expect(text).toContain('Despacho publicado')
    })

    it('renderiza estado vazio para destaques operacionais ausentes', async () => {
        const folder = defaultFolder()

        folder.summary.next_event = null

        folder.summary.latest_movement = null

        const { wrapper } = await mountPage({
            folder,
        })

        const text = wrapper.text()

        expect(text).toContain('Nenhum compromisso agendado.')

        expect(text).toContain('Nenhuma movimentação registrada.')
    })

    it('renderiza resumo operacional com segurança quando summary estiver ausente', async () => {
        const folder = defaultFolder()

        delete folder.summary

        const { wrapper } = await mountPage({
            folder,
        })

        const text = wrapper.text()

        expect(text).toContain('Visão operacional')

        expect(text).toContain('Documentos')

        expect(text).toContain('Tarefas pendentes')

        expect(text).toContain('Prazos pendentes')

        expect(text).toContain('Nenhum compromisso agendado.')

        expect(text).toContain('Nenhuma movimentação registrada.')
    })

    it('abre Documentos ao acionar o indicador de documentos', async () => {
        const { wrapper } = await mountPage()

        const shortcut = wrapper.get('[data-testid="folder-summary-documents"]')

        await shortcut.trigger('click')

        await wrapper.vm.$nextTick()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.props('modelValue')).toBe('documents')

        expect(
            wrapper
                .findComponent({
                    name: 'FolderDocuments',
                })
                .exists(),
        ).toBe(true)
    })

    it('abre Tarefas ao acionar o indicador de tarefas pendentes', async () => {
        const { wrapper } = await mountPage()

        const shortcut = wrapper.get('[data-testid="folder-summary-tasks"]')

        await shortcut.trigger('click')

        await wrapper.vm.$nextTick()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.props('modelValue')).toBe('tasks')

        expect(
            wrapper
                .findComponent({
                    name: 'FolderTasks',
                })
                .exists(),
        ).toBe(true)
    })

    it('abre Prazos ao acionar o indicador de prazos pendentes', async () => {
        const { wrapper } = await mountPage()

        const shortcut = wrapper.get('[data-testid="folder-summary-deadlines"]')

        await shortcut.trigger('click')

        await wrapper.vm.$nextTick()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.props('modelValue')).toBe('deadlines')

        expect(
            wrapper
                .findComponent({
                    name: 'FolderDeadlines',
                })
                .exists(),
        ).toBe(true)
    })

    it('abre Agenda ao acionar próximo compromisso', async () => {
        const { wrapper } = await mountPage()

        const shortcut = wrapper.get('[data-testid="folder-summary-next-event"]')

        await shortcut.trigger('click')

        await wrapper.vm.$nextTick()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.props('modelValue')).toBe('events')

        expect(
            wrapper
                .findComponent({
                    name: 'FolderEvents',
                })
                .exists(),
        ).toBe(true)
    })

    it('abre Movimentações ao acionar última movimentação', async () => {
        const { wrapper } = await mountPage()

        const shortcut = wrapper.get('[data-testid="folder-summary-latest-movement"]')

        await shortcut.trigger('click')

        await wrapper.vm.$nextTick()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.props('modelValue')).toBe('movements')

        expect(
            wrapper
                .findComponent({
                    name: 'FolderMovements',
                })
                .exists(),
        ).toBe(true)
    })

    it('recarrega a pasta quando a agenda é alterada', async () => {
        const { wrapper, fetchFolderSpy } = await mountPage()

        expect(fetchFolderSpy).toHaveBeenCalledTimes(1)

        expect(fetchFolderSpy).toHaveBeenLastCalledWith(10)

        const tabs = wrapper.findComponent(AppTabs)

        await tabs.vm.$emit('update:modelValue', 'events')

        await wrapper.vm.$nextTick()

        const component = wrapper.findComponent({
            name: 'FolderEvents',
        })

        expect(component.exists()).toBe(true)

        await component.vm.$emit('changed')

        await flushPromises()

        expect(fetchFolderSpy).toHaveBeenCalledTimes(2)

        expect(fetchFolderSpy).toHaveBeenLastCalledWith(10)
    })

    it('renderiza painel de atenção jurídica da pasta', async () => {
        const { wrapper } = await mountPage()

        const attention = wrapper.get('[data-testid="folder-attention"]')

        expect(attention.text()).toContain('Atenção jurídica')

        expect(attention.text()).toContain('Itens que exigem acompanhamento prioritário.')

        expect(attention.text()).toContain('Prazos prioritários')

        expect(attention.text()).toContain('Tarefas prioritárias')
    })

    it('renderiza prazos prioritários na visão geral', async () => {
        const { wrapper } = await mountPage()

        const attention = wrapper.get('[data-testid="folder-attention-deadlines"]')

        const text = attention.text()

        expect(text).toContain('Apresentar réplica')

        expect(text).toContain('Protocolar manifestação')

        expect(text).toContain('Juntar documentos')

        expect(text).toContain('Vencido')

        expect(text).toContain('Hoje')

        expect(text).toContain('Próximo')
    })

    it('renderiza tarefas prioritárias na visão geral', async () => {
        const { wrapper } = await mountPage()

        const attention = wrapper.get('[data-testid="folder-attention-tasks"]')

        const text = attention.text()

        expect(text).toContain('Revisar contestação')

        expect(text).toContain('Conferir documentos')

        expect(text).toContain('Contatar cliente')

        expect(text).toContain('Alta')

        expect(text).toContain('Média')

        expect(text).toContain('Sem vencimento')
    })

    it('renderiza estado vazio quando não existem itens de atenção', async () => {
        const folder = defaultFolder()

        folder.summary.attention = {
            deadlines: [],
            tasks: [],
        }

        const { wrapper } = await mountPage({
            folder,
        })

        expect(wrapper.text()).toContain('Nenhum prazo pendente exige atenção.')

        expect(wrapper.text()).toContain('Nenhuma tarefa pendente exige atenção.')
    })

    it('abre Prazos a partir do painel de atenção', async () => {
        const { wrapper } = await mountPage()

        await wrapper.get('[data-testid="folder-attention-deadlines-all"]').trigger('click')

        await wrapper.vm.$nextTick()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.props('modelValue')).toBe('deadlines')

        expect(
            wrapper
                .findComponent({
                    name: 'FolderDeadlines',
                })
                .exists(),
        ).toBe(true)
    })

    it('abre Tarefas a partir do painel de atenção', async () => {
        const { wrapper } = await mountPage()

        await wrapper.get('[data-testid="folder-attention-tasks-all"]').trigger('click')

        await wrapper.vm.$nextTick()

        const tabs = wrapper.findComponent(AppTabs)

        expect(tabs.props('modelValue')).toBe('tasks')

        expect(
            wrapper
                .findComponent({
                    name: 'FolderTasks',
                })
                .exists(),
        ).toBe(true)
    })
})
