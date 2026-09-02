import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import ClientSavePage from '@/views/clients/ClientSavePage.vue'

import { useClientsStore } from '@/stores/clients.js'
import { useMaritalStatusesStore } from '@/stores/marital-statuses.js'

import { getAddressByPostalCode } from '@/api/postal-codes.js'

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

vi.mock('@/api/postal-codes.js', () => ({
    getAddressByPostalCode: vi.fn(),
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

    it('preenche endereço consultando o endpoint interno ao completar o CEP', async () => {
        getAddressByPostalCode.mockResolvedValue({
            data: {
                data: {
                    address: 'Praça da Sé',
                    district: 'Sé',
                    city: 'São Paulo',
                    state: 'SP',
                },
            },
        })

        const { wrapper } = await mountPage()

        await wrapper.get('input[name="postal_code"]').setValue('01001-000')

        await vi.waitFor(() => {
            expect(getAddressByPostalCode).toHaveBeenCalledWith('01001000')
        })

        expect(wrapper.get('input[name="postal_code"]').element.value).toBe('01001000')
        expect(wrapper.get('input[name="address"]').element.value).toBe('Praça da Sé')
        expect(wrapper.get('input[name="district"]').element.value).toBe('Sé')
        expect(wrapper.get('input[name="city"]').element.value).toBe('São Paulo')
    })

    it('mantém preenchimento manual disponível quando o CEP não existe', async () => {
        getAddressByPostalCode.mockRejectedValue({
            response: {
                status: 404,
            },
        })

        const { wrapper } = await mountPage()

        await wrapper.get('input[name="postal_code"]').setValue('99999999')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('CEP não encontrado.')
        })

        expect(wrapper.get('input[name="address"]').attributes('disabled')).toBeUndefined()
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

    it('não envia formulário com CPF inválido', async () => {
        const { wrapper, clientsStore } = await mountPage()

        const createSpy = vi.spyOn(clientsStore, 'create')

        await wrapper.get('input[name="name"]').setValue('Cliente Teste')
        await wrapper.get('input[name="document"]').setValue('123.456.789-01')
        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()
        expect(wrapper.text()).toContain('Informe um CPF válido.')
        expect(wrapper.get('input[name="document"]').element.value).toBe('12345678901')
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

        await documentInput.setValue('52998224725')

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

        clientsStore.client = {
            id: 10,
            name: 'Cliente Existente',
            document: '52998224725',
            identity_document: '1234567890',
            identity_issuer: 'SSP/RS',
            marital_status_id: 2,
            profession: 'Advogada',
            address: 'Rua das Flores, 100',
            address_complement: 'Sala 201',
            district: 'Centro',
            city: 'Pelotas',
            postal_code: '96000000',
            phone: '53999999999',
            whatsapp: true,
            email: 'cliente@example.com',
        }

        vi.spyOn(maritalStatusesStore, 'fetchMaritalStatuses').mockResolvedValue([])

        const fetchClientSpy = vi.spyOn(clientsStore, 'fetchClient')

        await router.push('/clients/10/edit')

        await router.isReady()

        const wrapper = mount(ClientSavePage, {
            global: {
                plugins: [pinia, router],
            },
        })

        await flushPromises()

        expect(fetchClientSpy).not.toHaveBeenCalled()

        expect(wrapper.text()).toContain('Editar cliente')

        expect(wrapper.text()).toContain('Salvar alterações')

        expect(wrapper.get('input[name="name"]').element.value).toBe('Cliente Existente')

        expect(wrapper.get('input[name="identity_document"]').element.value).toBe('1234567890')
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
            document: '52998224725',
            identity_document: '1234567890',
            identity_issuer: 'SSP/RS',
            marital_status_id: 2,
            profession: 'Advogada',
            address: 'Rua das Flores, 100',
            address_complement: 'Sala 201',
            district: 'Centro',
            city: 'Pelotas',
            postal_code: '96000000',
            phone: '53999999999',
            whatsapp: true,
            email: 'cliente@example.com',
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

        await documentInput.setValue('52998224725')

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

        await documentInput.setValue('52998224725')

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
