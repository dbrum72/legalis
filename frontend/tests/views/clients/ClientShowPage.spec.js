import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import { createMemoryHistory, createRouter } from 'vue-router'

import ClientShowPage from '@/views/clients/ClientShowPage.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'

function defaultClient() {
    return {
        id: 10,
        name: 'Maria da Silva',
        document: '12345678901',
        identity_document: '1234567890',
        identity_issuer: 'SSP/RS',
        marital_status_id: 2,

        marital_status: {
            id: 2,
            name: 'Casado(a)',
        },

        profession: 'Advogada',

        phone: '53999998888',
        whatsapp: true,
        email: 'maria@example.com',

        address: 'Rua das Flores, 100',
        address_complement: 'Sala 201',
        district: 'Centro',
        city: 'Pelotas',
        postal_code: '96000000',
    }
}

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
                path: '/clients/:id',
                name: 'clients.show',
                component: ClientShowPage,
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

function findButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

async function mountPage({ client = defaultClient(), permissions = [], fetchError = null } = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push('/clients/10')

    await router.isReady()

    const authStore = useAuthStore()

    authStore.permissions = permissions

    const clientsStore = useClientsStore()

    clientsStore.client = client

    const fetchClientSpy = vi.spyOn(clientsStore, 'fetchClient')

    if (fetchError) {
        fetchClientSpy.mockRejectedValue(fetchError)
    } else {
        fetchClientSpy.mockResolvedValue(client)
    }

    const wrapper = mount(ClientShowPage, {
        global: {
            plugins: [pinia, router],
        },
    })

    await flushPromises()

    return {
        wrapper,
        router,
        authStore,
        clientsStore,
        fetchClientSpy,
    }
}

describe('ClientShowPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('carrega cliente pelo id da rota ao montar', async () => {
        const { fetchClientSpy } = await mountPage()

        expect(fetchClientSpy).toHaveBeenCalledTimes(1)

        expect(fetchClientSpy).toHaveBeenCalledWith(10)
    })

    it('renderiza titulo e descricao da pagina', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Detalhes do cliente')

        expect(wrapper.text()).toContain('Consulte os dados cadastrais do cliente.')
    })

    it('renderiza dados de identificacao', async () => {
        const { wrapper } = await mountPage()

        const text = wrapper.text()

        expect(text).toContain('Identificação')

        expect(text).toContain('Maria da Silva')

        expect(text).toContain('12345678901')

        expect(text).toContain('1234567890')

        expect(text).toContain('SSP/RS')

        expect(text).toContain('Casado(a)')

        expect(text).toContain('Advogada')
    })

    it('renderiza dados de contato', async () => {
        const { wrapper } = await mountPage()

        const text = wrapper.text()

        expect(text).toContain('Contato')

        expect(text).toContain('53999998888')

        expect(text).toContain('maria@example.com')

        expect(text).toContain('WhatsApp')

        expect(text).toContain('Sim')
    })

    it('renderiza dados de endereco', async () => {
        const { wrapper } = await mountPage()

        const text = wrapper.text()

        expect(text).toContain('Endereço')

        expect(text).toContain('Rua das Flores, 100')

        expect(text).toContain('Sala 201')

        expect(text).toContain('Centro')

        expect(text).toContain('Pelotas')

        expect(text).toContain('96000000')
    })

    it('renderiza valores ausentes com travessao', async () => {
        const { wrapper } = await mountPage({
            client: {
                id: 10,
                name: 'Cliente incompleto',
                document: '12345678901',
                identity_document: null,
                identity_issuer: null,
                marital_status_id: null,
                marital_status: null,
                profession: null,
                phone: null,
                whatsapp: false,
                email: null,
                address: null,
                address_complement: null,
                district: null,
                city: null,
                postal_code: null,
            },
        })

        expect(wrapper.text()).toContain('Cliente incompleto')

        expect(wrapper.text()).toContain('—')
    })

    it('renderiza Nao quando telefone nao possui whatsapp', async () => {
        const client = defaultClient()

        client.whatsapp = false

        const { wrapper } = await mountPage({
            client,
        })

        expect(wrapper.text()).toContain('Não')
    })

    it('exibe erro quando carregamento do cliente falha', async () => {
        const { wrapper } = await mountPage({
            fetchError: new Error('Falha ao carregar'),
        })

        expect(wrapper.text()).toContain('Não foi possível carregar os dados do cliente.')

        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })

    it('nao mostra Editar sem clients.update', async () => {
        const { wrapper } = await mountPage({
            permissions: [],
        })

        expect(findButton(wrapper, 'Editar')).toBeUndefined()
    })

    it('mostra Editar com clients.update', async () => {
        const { wrapper } = await mountPage({
            permissions: ['clients.update'],
        })

        expect(findButton(wrapper, 'Editar')).toBeTruthy()
    })

    it('navega para edicao do cliente', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['clients.update'],
        })

        const button = findButton(wrapper, 'Editar')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('clients.edit')

        expect(router.currentRoute.value.params.id).toBe('10')
    })

    it('mostra botao Voltar', async () => {
        const { wrapper } = await mountPage()

        expect(findButton(wrapper, 'Voltar')).toBeTruthy()
    })

    it('Voltar navega para listagem de clientes', async () => {
        const { wrapper, router } = await mountPage()

        const button = findButton(wrapper, 'Voltar')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('clients')
    })
})
