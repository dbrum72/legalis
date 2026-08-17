import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import { createMemoryHistory, createRouter } from 'vue-router'

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
                path: '/clients/:id/edit',

                name: 'clients.edit',

                component: ClientSavePage,
            },
        ],
    })
}

async function mountEditPage() {
    const pinia = createPinia()

    setActivePinia(pinia)

    const clientsStore = useClientsStore()

    const maritalStatusesStore = useMaritalStatusesStore()

    /*
     * Registro resumido vindo da listagem.
     */
    clientsStore.clients = [
        {
            id: 10,
            name: 'Maria da Silva',
            document: '12345678901',
            phone: '53999999999',
            email: 'maria@example.com',

            marital_status: {
                id: 2,
                name: 'Casado(a)',
            },
        },
    ]

    /*
     * Registro completo já carregado
     * pela ClientShowPage.
     */
    clientsStore.client = {
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
        address: 'Rua das Flores, 100',
        address_complement: 'Sala 201',
        district: 'Centro',
        city: 'Pelotas',
        postal_code: '96000000',
        phone: '53999999999',
        whatsapp: true,
        email: 'maria@example.com',
    }

    vi.spyOn(maritalStatusesStore, 'fetchMaritalStatuses').mockResolvedValue([])

    const fetchClientSpy = vi.spyOn(clientsStore, 'fetchClient')

    const router = createTestRouter()

    await router.push('/clients/10/edit')

    await router.isReady()

    const wrapper = mount(ClientSavePage, {
        global: {
            plugins: [pinia, router],
        },
    })

    await flushPromises()

    return {
        wrapper,
        clientsStore,
        fetchClientSpy,
    }
}

describe('ClientSavePage - fluxo Detalhes para Editar', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('usa o cliente completo já carregado pela página de detalhes', async () => {
        const { wrapper } = await mountEditPage()

        expect(wrapper.get('input[name="name"]').element.value).toBe('Maria da Silva')

        expect(wrapper.get('input[name="identity_document"]').element.value).toBe('1234567890')

        expect(wrapper.get('input[name="identity_issuer"]').element.value).toBe('SSP/RS')

        expect(wrapper.get('input[name="profession"]').element.value).toBe('Advogada')

        expect(wrapper.get('input[name="address"]').element.value).toBe('Rua das Flores, 100')

        expect(wrapper.get('input[name="city"]').element.value).toBe('Pelotas')
    })

    it('não faz nova requisição quando o cliente completo atual corresponde ao id da rota', async () => {
        const { fetchClientSpy } = await mountEditPage()

        expect(fetchClientSpy).not.toHaveBeenCalled()
    })
})
