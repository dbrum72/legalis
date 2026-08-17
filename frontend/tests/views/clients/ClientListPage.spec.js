import { beforeEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import ClientListPage from '@/views/clients/ClientListPage.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'

vi.mock('@/api/clients.js', () => ({
    listClients: vi.fn(),

    getClient: vi.fn(),

    createClient: vi.fn(),

    updateClient: vi.fn(),

    deleteClient: vi.fn(),
}))

vi.mock('@/api/auth.js', () => ({
    login: vi.fn(),

    logout: vi.fn(),

    me: vi.fn(),

    refresh: vi.fn(),

    context: vi.fn(),
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
                path: '/clients/:id',

                name: 'clients.show',

                component: {
                    template: '<div>Detalhes do cliente</div>',
                },
            },

            {
                path: '/clients/:id/edit',

                name: 'clients.edit',

                component: {
                    template: '<div>Editar cliente</div>',
                },
            },
        ],
    })
}

async function mountPage({ permissions = [], clients = [] } = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push('/clients')

    await router.isReady()

    const authStore = useAuthStore()

    const clientsStore = useClientsStore()

    authStore.permissions = permissions

    vi.spyOn(clientsStore, 'fetchClients').mockImplementation(async () => {
        clientsStore.clients = clients

        return clients
    })

    const wrapper = mount(ClientListPage, {
        global: {
            plugins: [pinia, router],
        },
    })

    await vi.waitFor(() => {
        expect(clientsStore.fetchClients).toHaveBeenCalled()
    })

    return {
        wrapper,
        router,
        authStore,
        clientsStore,
    }
}

function findButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

function findTeleportedButton(label) {
    return Array.from(document.querySelectorAll('button')).find(
        (button) => button.textContent.trim() === label,
    )
}

describe('ClientListPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega clientes ao montar', async () => {
        const { clientsStore } = await mountPage()

        expect(clientsStore.fetchClients).toHaveBeenCalledTimes(1)
    })

    it('renderiza título da página', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Clientes')
    })

    it('renderiza clientes retornados pela store', async () => {
        const { wrapper } = await mountPage({
            clients: [
                {
                    id: 1,

                    name: 'Maria da Silva',

                    document: '12345678901',

                    phone: '53999999999',

                    email: 'maria@example.com',

                    marital_status: {
                        id: 1,

                        name: 'solteiro(a)',
                    },
                },

                {
                    id: 2,

                    name: 'João Souza',

                    document: '12345678000199',

                    phone: null,

                    email: null,

                    marital_status: {
                        id: 2,

                        name: 'casado(a)',
                    },
                },
            ],
        })

        expect(wrapper.text()).toContain('Maria da Silva')

        expect(wrapper.text()).toContain('12345678901')

        expect(wrapper.text()).toContain('solteiro(a)')

        expect(wrapper.text()).toContain('João Souza')

        expect(wrapper.text()).toContain('casado(a)')
    })

    it('renderiza estado vazio quando não existem clientes', async () => {
        const { wrapper } = await mountPage({
            clients: [],
        })

        expect(wrapper.text()).toContain('Nenhum cliente cadastrado.')
    })

    it('renderiza estado civil vazio sem quebrar', async () => {
        const { wrapper } = await mountPage({
            clients: [
                {
                    id: 1,

                    name: 'Cliente Sem Estado Civil',

                    document: '12345678901',

                    phone: null,

                    email: null,

                    marital_status: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('Cliente Sem Estado Civil')

        expect(wrapper.text()).toContain('—')
    })

    it('não mostra botão Novo cliente sem clients.create', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view'],
        })

        expect(wrapper.text()).not.toContain('Novo cliente')
    })

    it('mostra botão Novo cliente com clients.create', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view', 'clients.create'],
        })

        expect(wrapper.text()).toContain('Novo cliente')
    })

    it('mostra Visualizar para cliente listado', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view'],

            clients: [
                {
                    id: 10,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        expect(findButton(wrapper, 'Visualizar')).toBeTruthy()
    })

    it('navega para detalhes do cliente selecionado', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['clients.view'],

            clients: [
                {
                    id: 10,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        const button = findButton(wrapper, 'Visualizar')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('clients.show')

            expect(router.currentRoute.value.params.id).toBe('10')
        })
    })

    it('não mostra Editar sem clients.update', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view'],

            clients: [
                {
                    id: 1,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        expect(wrapper.text()).not.toContain('Editar')
    })

    it('mostra Editar com clients.update', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view', 'clients.update'],

            clients: [
                {
                    id: 1,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('Editar')
    })

    it('não mostra Excluir sem clients.delete', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view'],

            clients: [
                {
                    id: 1,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        expect(wrapper.text()).not.toContain('Excluir')
    })

    it('mostra Excluir com clients.delete', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view', 'clients.delete'],

            clients: [
                {
                    id: 1,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('Excluir')
    })

    it('mostra ações independentes conforme permissions', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view', 'clients.update'],

            clients: [
                {
                    id: 1,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('Visualizar')

        expect(wrapper.text()).toContain('Editar')

        expect(wrapper.text()).not.toContain('Excluir')
    })

    it('navega para cadastro ao clicar em Novo cliente', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['clients.view', 'clients.create'],
        })

        const button = findButton(wrapper, 'Novo cliente')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('clients.create')
        })
    })

    it('navega para edição do cliente selecionado', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['clients.view', 'clients.update'],

            clients: [
                {
                    id: 10,

                    name: 'Cliente A',

                    document: '12345678901',

                    phone: null,

                    email: null,

                    marital_status: null,
                },
            ],
        })

        const button = findButton(wrapper, 'Editar')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('clients.edit')

            expect(router.currentRoute.value.params.id).toBe('10')
        })
    })

    it('abre confirmação ao clicar em Excluir', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.view', 'clients.delete'],

            clients: [
                {
                    id: 10,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        const deleteButton = findButton(wrapper, 'Excluir')

        expect(deleteButton).toBeTruthy()

        await deleteButton.trigger('click')

        expect(document.body.textContent).toContain('Excluir cliente')

        expect(document.body.textContent).toContain(
            'Deseja realmente excluir o cliente "Cliente A"?',
        )
    })

    it('cancela exclusão sem remover cliente', async () => {
        const { wrapper, clientsStore } = await mountPage({
            permissions: ['clients.view', 'clients.delete'],

            clients: [
                {
                    id: 10,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        const removeSpy = vi.spyOn(clientsStore, 'remove')

        const deleteButton = findButton(wrapper, 'Excluir')

        await deleteButton.trigger('click')

        const cancelButton = findTeleportedButton('Cancelar')

        expect(cancelButton).toBeTruthy()

        cancelButton.click()

        await wrapper.vm.$nextTick()

        expect(removeSpy).not.toHaveBeenCalled()

        expect(document.querySelector('.app-confirm-dialog')).toBeNull()
    })

    it('confirma exclusão e remove cliente', async () => {
        const { wrapper, clientsStore } = await mountPage({
            permissions: ['clients.view', 'clients.delete'],

            clients: [
                {
                    id: 10,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        const removeSpy = vi.spyOn(clientsStore, 'remove').mockResolvedValue()

        const deleteButton = findButton(wrapper, 'Excluir')

        await deleteButton.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(removeSpy).toHaveBeenCalledTimes(1)

            expect(removeSpy).toHaveBeenCalledWith(10)
        })

        await vi.waitFor(() => {
            expect(document.querySelector('.app-confirm-dialog')).toBeNull()
        })
    })

    it('exibe erro quando exclusão falha', async () => {
        const { wrapper, clientsStore } = await mountPage({
            permissions: ['clients.view', 'clients.delete'],

            clients: [
                {
                    id: 10,

                    name: 'Cliente A',

                    document: '12345678901',

                    marital_status: null,
                },
            ],
        })

        vi.spyOn(clientsStore, 'remove').mockRejectedValue(new Error('Falha ao excluir'))

        const deleteButton = findButton(wrapper, 'Excluir')

        await deleteButton.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível excluir o cliente. Tente novamente.')
        })

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()
    })
})
