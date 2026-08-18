import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import FolderShowPage from '@/views/folders/FolderShowPage.vue'

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

        expect(wrapper.text()).toContain('Maria da Silva')

        expect(wrapper.text()).toContain('—')
    })

    it('renderiza documentos da pasta com o id atual', async () => {
        const { wrapper } = await mountPage()

        const component = wrapper.findComponent({
            name: 'FolderDocuments',
        })

        expect(component.exists()).toBe(true)

        expect(component.props('folderId')).toBe(10)
    })

    it('renderiza movimentações da pasta com o id atual', async () => {
        const { wrapper } = await mountPage()

        const component = wrapper.findComponent({
            name: 'FolderMovements',
        })

        expect(component.exists()).toBe(true)

        expect(component.props('folderId')).toBe(10)
    })

    it('renderiza prazos da pasta com o id atual', async () => {
        const { wrapper } = await mountPage()

        const component = wrapper.findComponent({
            name: 'FolderDeadlines',
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

    it('renderiza agenda da pasta com o id atual', async () => {
        const { wrapper } = await mountPage()

        const component = wrapper.findComponent({
            name: 'FolderEvents',
        })

        expect(component.exists()).toBe(true)

        expect(component.props('folderId')).toBe(10)
    })
})
