import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import ClientSavePage from '@/views/clients/ClientSavePage.vue'

import { useClientsStore } from '@/stores/clients.js'
import { useMaritalStatusesStore } from '@/stores/marital-statuses.js'

vi.mock('@/api/clients.js', () => ({
    listClients: vi.fn(),
    getClient: vi.fn(),
    createClient: vi.fn(),
    updateClient: vi.fn(),
    deleteClient: vi.fn(),
}))

vi.mock('@/api/marital-statuses.js', () => ({
    listMaritalStatuses: vi.fn(),
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
                component: ClientSavePage,
            },
            {
                path: '/clients/:id/edit',
                name: 'clients.edit',
                component: ClientSavePage,
            },
        ],
    })
}

async function mountPage(route = '/clients/new') {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    const clientsStore = useClientsStore()

    const maritalStatusesStore = useMaritalStatusesStore()

    vi.spyOn(maritalStatusesStore, 'fetchMaritalStatuses').mockResolvedValue([])

    await router.push(route)
    await router.isReady()

    const wrapper = mount(ClientSavePage, {
        global: {
            plugins: [pinia, router],
        },
    })

    await vi.waitFor(() => {
        expect(maritalStatusesStore.fetchMaritalStatuses).toHaveBeenCalled()
    })

    return {
        wrapper,
        router,
        clientsStore,
        maritalStatusesStore,
    }
}

describe('ClientSavePage', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('renderiza modo de cadastro', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Novo cliente')

        expect(wrapper.text()).toContain('Cadastrar cliente')
    })

    it('carrega estados civis ao montar', async () => {
        const { maritalStatusesStore } = await mountPage()

        expect(maritalStatusesStore.fetchMaritalStatuses).toHaveBeenCalledTimes(1)
    })

    it('não envia formulário sem nome', async () => {
        const { wrapper, clientsStore } = await mountPage()

        const createSpy = vi.spyOn(clientsStore, 'create')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()

        expect(wrapper.text()).toContain('Informe o nome.')
    })

    it('não envia formulário sem documento', async () => {
        const { wrapper, clientsStore } = await mountPage()

        const createSpy = vi.spyOn(clientsStore, 'create')

        const inputs = wrapper.findAll('input')

        const nameInput = inputs.find((input) => input.attributes('name') === 'name')

        await nameInput.setValue('Cliente Teste')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()

        expect(wrapper.text()).toContain('Informe o CPF ou CNPJ.')
    })

    it('cria cliente e retorna para listagem', async () => {
        const { wrapper, router, clientsStore } = await mountPage()

        vi.spyOn(clientsStore, 'create').mockResolvedValue({
            id: 1,
        })

        const inputs = wrapper.findAll('input')

        const nameInput = inputs.find((input) => input.attributes('name') === 'name')

        const documentInput = inputs.find((input) => input.attributes('name') === 'document')

        await nameInput.setValue('Cliente Teste')

        await documentInput.setValue('12345678901')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(clientsStore.create).toHaveBeenCalled()
        })

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('clients')
        })
    })

    it('renderiza modo de edição', async () => {
        const pinia = createPinia()

        setActivePinia(pinia)

        const router = createTestRouter()

        const clientsStore = useClientsStore()

        const maritalStatusesStore = useMaritalStatusesStore()

        clientsStore.clients = [
            {
                id: 10,
                name: 'Cliente Existente',
                document: '12345678901',
            },
        ]

        vi.spyOn(maritalStatusesStore, 'fetchMaritalStatuses').mockResolvedValue([])

        const fetchClientSpy = vi.spyOn(clientsStore, 'fetchClient')

        await router.push('/clients/10/edit')

        await router.isReady()

        const wrapper = mount(ClientSavePage, {
            global: {
                plugins: [pinia, router],
            },
        })

        await vi.waitFor(() => {
            expect(maritalStatusesStore.fetchMaritalStatuses).toHaveBeenCalled()
        })

        expect(fetchClientSpy).not.toHaveBeenCalled()

        expect(wrapper.text()).toContain('Editar cliente')

        expect(wrapper.text()).toContain('Salvar alterações')
    })

    it('carrega cliente remoto quando não está em cache', async () => {
        const pinia = createPinia()

        setActivePinia(pinia)

        const router = createTestRouter()

        const clientsStore = useClientsStore()

        const maritalStatusesStore = useMaritalStatusesStore()

        vi.spyOn(maritalStatusesStore, 'fetchMaritalStatuses').mockResolvedValue([])

        const fetchClientSpy = vi.spyOn(clientsStore, 'fetchClient').mockResolvedValue({
            id: 10,
            name: 'Cliente Existente',
            document: '12345678901',
        })

        await router.push('/clients/10/edit')

        await router.isReady()

        mount(ClientSavePage, {
            global: {
                plugins: [pinia, router],
            },
        })

        await flushPromises()

        expect(fetchClientSpy).toHaveBeenCalledTimes(1)

        expect(fetchClientSpy).toHaveBeenCalledWith(10)
    })

    it('exibe erros 422 da api', async () => {
        const { wrapper, clientsStore } = await mountPage()

        vi.spyOn(clientsStore, 'create').mockRejectedValue({
            response: {
                status: 422,
                data: {
                    errors: {
                        document: ['Documento já cadastrado.'],
                    },
                },
            },
        })

        const inputs = wrapper.findAll('input')

        const nameInput = inputs.find((input) => input.attributes('name') === 'name')

        const documentInput = inputs.find((input) => input.attributes('name') === 'document')

        await nameInput.setValue('Cliente Teste')

        await documentInput.setValue('12345678901')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Documento já cadastrado.')
        })
    })

    it('exibe erro genérico', async () => {
        const { wrapper, clientsStore } = await mountPage()

        vi.spyOn(clientsStore, 'create').mockRejectedValue({
            response: {
                status: 500,
            },
        })

        const inputs = wrapper.findAll('input')

        const nameInput = inputs.find((input) => input.attributes('name') === 'name')

        const documentInput = inputs.find((input) => input.attributes('name') === 'document')

        await nameInput.setValue('Cliente Teste')

        await documentInput.setValue('12345678901')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível salvar o cliente. Tente novamente.')
        })
    })

    it('cancelar retorna para listagem', async () => {
        const { wrapper, router } = await mountPage()

        const button = wrapper.findAll('button').find((item) => item.text() === 'Cancelar')

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('clients')
        })
    })
})
