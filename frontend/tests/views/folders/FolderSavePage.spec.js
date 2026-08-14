import {
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest'

import {
    flushPromises,
    mount,
} from '@vue/test-utils'

import {
    createMemoryHistory,
    createRouter,
} from 'vue-router'

import {
    createPinia,
    setActivePinia,
} from 'pinia'

import FolderSavePage from '@/views/folders/FolderSavePage.vue'
import FolderClients from '@/views/folders/components/FolderClients.vue'

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

vi.mock('@/api/clients.js', () => ({
    listClients: vi.fn(() =>
        Promise.resolve({
            data: [],
        }),
    ),

    getClient: vi.fn(),
    createClient: vi.fn(),
    updateClient: vi.fn(),
    deleteClient: vi.fn(),
}))

vi.mock('@/api/qualifications.js', () => ({
    listQualifications: vi.fn(() =>
        Promise.resolve({
            data: [],
        }),
    ),
}))

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
        history:
            createMemoryHistory(),

        routes: [
            {
                path: '/folders',
                name: 'folders',

                component: {
                    template:
                        '<div>Pastas</div>',
                },
            },
            {
                path: '/folders/new',
                name: 'folders.create',
                component:
                    FolderSavePage,
            },
            {
                path:
                    '/folders/:id/edit',

                name: 'folders.edit',
                component:
                    FolderSavePage,
            },
        ],
    })
}

function findInput(
    wrapper,
    name,
) {
    return wrapper
        .findAll('input')
        .find(
            (input) =>
                input.attributes(
                    'name',
                ) === name,
        )
}

async function mountPage({
    route = '/folders/new',
    folders = [],
    fetchFolderResult = null,
    permissions = [],
} = {}) {
    const pinia =
        createPinia()

    setActivePinia(pinia)

    const router =
        createTestRouter()

    const authStore =
        useAuthStore()

    const foldersStore =
        useFoldersStore()

    authStore.permissions = [
        ...permissions,
    ]

    foldersStore.folders = [
        ...folders,
    ]

    const fetchFolderSpy =
        vi.spyOn(
            foldersStore,
            'fetchFolder',
        )

    if (fetchFolderResult) {
        fetchFolderSpy
            .mockResolvedValue(
                fetchFolderResult,
            )
    } else if (
        route.includes('/edit')
    ) {
        const id =
            Number(
                route
                    .split('/')[2],
            )

        const cached =
            foldersStore.getById(id)

        fetchFolderSpy
            .mockResolvedValue(
                cached
                    ? {
                        ...cached,

                        folder_clients:
                            Array.isArray(
                                cached
                                    .folder_clients,
                            )
                                ? cached
                                    .folder_clients
                                : [],
                    }
                    : null,
            )
    }

    await router.push(route)
    await router.isReady()

    const wrapper =
        mount(
            FolderSavePage,
            {
                global: {
                    plugins: [
                        pinia,
                        router,
                    ],
                },
            },
        )

    await flushPromises()

    return {
        wrapper,
        router,
        authStore,
        foldersStore,
        fetchFolderSpy,
    }
}

describe(
    'FolderSavePage',
    () => {
        beforeEach(() => {
            vi.clearAllMocks()
        })

        it(
            'renderiza modo de cadastro',
            async () => {
                const {
                    wrapper,
                } = await mountPage()

                expect(
                    wrapper.text(),
                ).toContain(
                    'Nova pasta',
                )

                expect(
                    wrapper.text(),
                ).toContain(
                    'Informe os dados para cadastrar uma nova pasta jurídica.',
                )

                expect(
                    wrapper.text(),
                ).toContain(
                    'Cadastrar pasta',
                )
            },
        )

        it(
            'não renderiza gestão de partes no cadastro',
            async () => {
                const {
                    wrapper,
                } = await mountPage()

                expect(
                    wrapper
                        .findComponent(
                            FolderClients,
                        )
                        .exists(),
                ).toBe(false)
            },
        )

        it(
            'limpa pasta atual ao entrar em modo de cadastro',
            async () => {
                const pinia =
                    createPinia()

                setActivePinia(
                    pinia,
                )

                const foldersStore =
                    useFoldersStore()

                foldersStore.folder = {
                    id: 10,
                    name:
                        'Pasta antiga',
                }

                const clearCurrentSpy =
                    vi.spyOn(
                        foldersStore,
                        'clearCurrent',
                    )

                const router =
                    createTestRouter()

                await router.push(
                    '/folders/new',
                )

                await router.isReady()

                mount(
                    FolderSavePage,
                    {
                        global: {
                            plugins: [
                                pinia,
                                router,
                            ],
                        },
                    },
                )

                await flushPromises()

                expect(
                    clearCurrentSpy,
                ).toHaveBeenCalledTimes(
                    1,
                )

                expect(
                    foldersStore.folder,
                ).toBeNull()
            },
        )

        it(
            'não envia formulário sem nome',
            async () => {
                const {
                    wrapper,
                    foldersStore,
                } = await mountPage()

                const createSpy =
                    vi.spyOn(
                        foldersStore,
                        'create',
                    )

                await wrapper
                    .get('form')
                    .trigger('submit')

                expect(
                    createSpy,
                ).not.toHaveBeenCalled()

                expect(
                    wrapper.text(),
                ).toContain(
                    'Informe o nome.',
                )
            },
        )

        it(
            'cria pasta com payload normalizado',
            async () => {
                const {
                    wrapper,
                    foldersStore,
                } = await mountPage()

                const createSpy =
                    vi.spyOn(
                        foldersStore,
                        'create',
                    ).mockResolvedValue({
                        id: 1,

                        name:
                            'Ação indenizatória',

                        process_number:
                            null,
                    })

                const nameInput =
                    findInput(
                        wrapper,
                        'name',
                    )

                const processInput =
                    findInput(
                        wrapper,
                        'process_number',
                    )

                await nameInput
                    .setValue(
                        '  Ação indenizatória  ',
                    )

                await processInput
                    .setValue('')

                await wrapper
                    .get('form')
                    .trigger('submit')

                await vi.waitFor(
                    () => {
                        expect(
                            createSpy,
                        ).toHaveBeenCalledWith({
                            name:
                                'Ação indenizatória',

                            process_number:
                                null,
                        })
                    },
                )
            },
        )

        it(
            'redireciona para edição após cadastro quando possui folders.update',
            async () => {
                const {
                    wrapper,
                    router,
                    foldersStore,
                } = await mountPage({
                    permissions: [
                        'folders.create',
                        'folders.update',
                    ],
                })

                vi.spyOn(
                    foldersStore,
                    'create',
                ).mockImplementation(
                    async (payload) => {
                        const created = {
                            id: 25,
                            ...payload,
                        }

                        foldersStore
                            .folders
                            .push(created)

                        return created
                    },
                )

                vi.spyOn(
                    foldersStore,
                    'fetchFolder',
                ).mockResolvedValue({
                    id: 25,
                    name:
                        'Nova pasta',
                    process_number:
                        null,
                    folder_clients:
                        [],
                })

                const nameInput =
                    findInput(
                        wrapper,
                        'name',
                    )

                await nameInput
                    .setValue(
                        'Nova pasta',
                    )

                await wrapper
                    .get('form')
                    .trigger('submit')

                await vi.waitFor(
                    () => {
                        expect(
                            router
                                .currentRoute
                                .value.name,
                        ).toBe(
                            'folders.edit',
                        )
                    },
                )

                expect(
                    router
                        .currentRoute
                        .value.params.id,
                ).toBe('25')

                await vi.waitFor(
                    () => {
                        expect(
                            foldersStore
                                .fetchFolder,
                        ).toHaveBeenCalledWith(
                            25,
                        )
                    },
                )
            },
        )

        it(
            'redireciona para listagem após cadastro quando não possui folders.update',
            async () => {
                const {
                    wrapper,
                    router,
                    foldersStore,
                } = await mountPage({
                    permissions: [
                        'folders.create',
                    ],
                })

                vi.spyOn(
                    foldersStore,
                    'create',
                ).mockResolvedValue({
                    id: 25,
                    name:
                        'Nova pasta',
                    process_number:
                        null,
                })

                const nameInput =
                    findInput(
                        wrapper,
                        'name',
                    )

                await nameInput
                    .setValue(
                        'Nova pasta',
                    )

                await wrapper
                    .get('form')
                    .trigger('submit')

                await vi.waitFor(
                    () => {
                        expect(
                            router
                                .currentRoute
                                .value.name,
                        ).toBe(
                            'folders',
                        )
                    },
                )
            },
        )

        it(
            'usa cache para preencher imediatamente e busca detalhe completo',
            async () => {
                const {
                    wrapper,
                    fetchFolderSpy,
                } = await mountPage({
                    route:
                        '/folders/10/edit',

                    folders: [
                        {
                            id: 10,

                            name:
                                'Pasta existente',

                            process_number:
                                '5000000-00.2026.8.21.0001',
                        },
                    ],

                    fetchFolderResult: {
                        id: 10,

                        name:
                            'Pasta existente',

                        process_number:
                            '5000000-00.2026.8.21.0001',

                        folder_clients:
                            [],
                    },
                })

                expect(
                    fetchFolderSpy,
                ).toHaveBeenCalledWith(
                    10,
                )

                const nameInput =
                    findInput(
                        wrapper,
                        'name',
                    )

                expect(
                    nameInput
                        .element.value,
                ).toBe(
                    'Pasta existente',
                )
            },
        )

        it(
            'carrega relacionamentos da pasta no modo de edição',
            async () => {
                const {
                    foldersStore,
                } = await mountPage({
                    route:
                        '/folders/10/edit',

                    folders: [
                        {
                            id: 10,
                            name:
                                'Pasta A',
                            process_number:
                                null,
                        },
                    ],

                    fetchFolderResult: {
                        id: 10,
                        name:
                            'Pasta A',

                        process_number:
                            null,

                        folder_clients: [
                            {
                                id: 100,

                                client: {
                                    id: 20,
                                    name:
                                        'Cliente A',
                                },

                                qualification: {
                                    id: 30,
                                    name:
                                        'Autor',
                                },
                            },
                        ],
                    },
                })

                /*
                 * fetchFolder está mockado nesta
                 * suíte. Portanto, aplicamos aqui
                 * o mesmo efeito de estado da action
                 * real para validar o contrato.
                 */
                foldersStore.folder = {
                    id: 10,
                    name:
                        'Pasta A',

                    process_number:
                        null,

                    folder_clients: [
                        {
                            id: 100,

                            client: {
                                id: 20,
                                name:
                                    'Cliente A',
                            },

                            qualification: {
                                id: 30,
                                name:
                                    'Autor',
                            },
                        },
                    ],
                }

                expect(
                    foldersStore
                        .folderClients,
                ).toHaveLength(1)

                expect(
                    foldersStore
                        .folderClients[0]
                        .client.name,
                ).toBe(
                    'Cliente A',
                )
            },
        )

        it(
            'renderiza gestão de partes no modo de edição',
            async () => {
                const {
                    wrapper,
                } = await mountPage({
                    route:
                        '/folders/10/edit',

                    folders: [
                        {
                            id: 10,
                            name:
                                'Pasta existente',

                            process_number:
                                null,
                        },
                    ],

                    fetchFolderResult: {
                        id: 10,
                        name:
                            'Pasta existente',

                        process_number:
                            null,

                        folder_clients:
                            [],
                    },
                })

                expect(
                    wrapper
                        .findComponent(
                            FolderClients,
                        )
                        .exists(),
                ).toBe(true)
            },
        )

        it(
            'informa folderId correto para gestão de partes',
            async () => {
                const {
                    wrapper,
                } = await mountPage({
                    route:
                        '/folders/10/edit',

                    fetchFolderResult: {
                        id: 10,
                        name:
                            'Pasta existente',

                        process_number:
                            null,

                        folder_clients:
                            [],
                    },
                })

                const folderClients =
                    wrapper.findComponent(
                        FolderClients,
                    )

                expect(
                    folderClients.props(
                        'folderId',
                    ),
                ).toBe(10)
            },
        )

        it(
            'carrega pasta remotamente quando não está em cache',
            async () => {
                const {
                    wrapper,
                    fetchFolderSpy,
                } = await mountPage({
                    route:
                        '/folders/10/edit',

                    fetchFolderResult: {
                        id: 10,

                        name:
                            'Pasta remota',

                        process_number:
                            '5000001-00.2026.8.21.0001',

                        folder_clients:
                            [],
                    },
                })

                expect(
                    fetchFolderSpy,
                ).toHaveBeenCalledWith(
                    10,
                )

                const nameInput =
                    findInput(
                        wrapper,
                        'name',
                    )

                expect(
                    nameInput
                        .element.value,
                ).toBe(
                    'Pasta remota',
                )
            },
        )

        it(
            'atualiza pasta em modo de edição',
            async () => {
                const {
                    wrapper,
                    foldersStore,
                } = await mountPage({
                    route:
                        '/folders/10/edit',

                    folders: [
                        {
                            id: 10,

                            name:
                                'Pasta antiga',

                            process_number:
                                null,
                        },
                    ],

                    fetchFolderResult: {
                        id: 10,

                        name:
                            'Pasta antiga',

                        process_number:
                            null,

                        folder_clients:
                            [],
                    },
                })

                const updateSpy =
                    vi.spyOn(
                        foldersStore,
                        'update',
                    ).mockResolvedValue({
                        id: 10,

                        name:
                            'Pasta atualizada',

                        process_number:
                            '5000002-00.2026.8.21.0001',
                    })

                const nameInput =
                    findInput(
                        wrapper,
                        'name',
                    )

                const processInput =
                    findInput(
                        wrapper,
                        'process_number',
                    )

                await nameInput
                    .setValue(
                        '  Pasta atualizada  ',
                    )

                await processInput
                    .setValue(
                        '5000002-00.2026.8.21.0001',
                    )

                await wrapper
                    .get('form')
                    .trigger('submit')

                await vi.waitFor(
                    () => {
                        expect(
                            updateSpy,
                        ).toHaveBeenCalledWith(
                            10,
                            {
                                name:
                                    'Pasta atualizada',

                                process_number:
                                    '5000002-00.2026.8.21.0001',
                            },
                        )
                    },
                )
            },
        )

        it(
            'redireciona para listagem após edição',
            async () => {
                const {
                    wrapper,
                    router,
                    foldersStore,
                } = await mountPage({
                    route:
                        '/folders/10/edit',

                    folders: [
                        {
                            id: 10,
                            name:
                                'Pasta A',
                            process_number:
                                null,
                        },
                    ],

                    fetchFolderResult: {
                        id: 10,
                        name:
                            'Pasta A',

                        process_number:
                            null,

                        folder_clients:
                            [],
                    },
                })

                vi.spyOn(
                    foldersStore,
                    'update',
                ).mockResolvedValue({
                    id: 10,
                    name:
                        'Pasta A',
                    process_number:
                        null,
                })

                await wrapper
                    .get('form')
                    .trigger('submit')

                await vi.waitFor(
                    () => {
                        expect(
                            router
                                .currentRoute
                                .value.name,
                        ).toBe(
                            'folders',
                        )
                    },
                )
            },
        )

        it(
            'exibe erros 422 retornados pela api',
            async () => {
                const {
                    wrapper,
                    foldersStore,
                } = await mountPage()

                vi.spyOn(
                    foldersStore,
                    'create',
                ).mockRejectedValue({
                    response: {
                        status: 422,

                        data: {
                            errors: {
                                name: [
                                    'Nome inválido.',
                                ],

                                process_number: [
                                    'Número do processo inválido.',
                                ],
                            },
                        },
                    },
                })

                const nameInput =
                    findInput(
                        wrapper,
                        'name',
                    )

                await nameInput
                    .setValue(
                        'Pasta Teste',
                    )

                await wrapper
                    .get('form')
                    .trigger('submit')

                await vi.waitFor(
                    () => {
                        expect(
                            wrapper.text(),
                        ).toContain(
                            'Nome inválido.',
                        )

                        expect(
                            wrapper.text(),
                        ).toContain(
                            'Número do processo inválido.',
                        )
                    },
                )
            },
        )

        it(
            'exibe erro genérico quando salvamento falha',
            async () => {
                const {
                    wrapper,
                    foldersStore,
                } = await mountPage()

                vi.spyOn(
                    foldersStore,
                    'create',
                ).mockRejectedValue(
                    new Error(
                        'Erro inesperado',
                    ),
                )

                const nameInput =
                    findInput(
                        wrapper,
                        'name',
                    )

                await nameInput
                    .setValue(
                        'Pasta Teste',
                    )

                await wrapper
                    .get('form')
                    .trigger('submit')

                await vi.waitFor(
                    () => {
                        expect(
                            wrapper.text(),
                        ).toContain(
                            'Não foi possível salvar a pasta. Tente novamente.',
                        )
                    },
                )
            },
        )

        it(
            'cancelar retorna para listagem',
            async () => {
                const {
                    wrapper,
                    router,
                } = await mountPage()

                const cancelButton =
                    wrapper
                        .findAll(
                            'button',
                        )
                        .find(
                            (
                                button,
                            ) =>
                                button.text() ===
                                'Cancelar',
                        )

                expect(
                    cancelButton,
                ).toBeTruthy()

                await cancelButton
                    .trigger('click')

                await vi.waitFor(
                    () => {
                        expect(
                            router
                                .currentRoute
                                .value.name,
                        ).toBe(
                            'folders',
                        )
                    },
                )
            },
        )
    },
)