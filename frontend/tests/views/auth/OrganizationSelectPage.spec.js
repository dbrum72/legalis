import { beforeEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import OrganizationSelectPage from '@/views/auth/OrganizationSelectPage.vue'

import { useAuthStore } from '@/stores/auth.js'

vi.mock('@/api/auth.js', () => ({
    context: vi.fn(),
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

vi.mock('@/api/tenant.js', () => ({
    getCurrentTenant: vi.fn(),
    setCurrentTenant: vi.fn(),
    removeCurrentTenant: vi.fn(),
}))

function createTestRouter(initialRoute = '/organizations/select') {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/organizations/select',

                name: 'organizations.select',

                component: OrganizationSelectPage,
            },

            {
                path: '/',

                name: 'dashboard',

                component: {
                    template: '<div>Dashboard</div>',
                },
            },

            {
                path: '/clients',

                name: 'clients',

                component: {
                    template: '<div>Clientes</div>',
                },
            },

            {
                path: '/login',

                name: 'login',

                component: {
                    template: '<div>Login</div>',
                },
            },
        ],
    })
}

async function mountPage(initialRoute = '/organizations/select') {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push(initialRoute)

    await router.isReady()

    const authStore = useAuthStore()

    authStore.token = 'jwt-token'

    authStore.user = {
        id: 1,

        name: 'Super Admin',

        email: 'super-admin@legalis.local',
    }

    const wrapper = mount(OrganizationSelectPage, {
        global: {
            plugins: [pinia, router],

            stubs: {
                AppLogo: {
                    template: '<div>Legalis</div>',
                },

                AppCard: {
                    template: '<section><slot /></section>',
                },

                AppIcon: {
                    template: '<span />',
                },

                AppButton: {
                    props: ['loading', 'disabled'],

                    emits: ['click'],

                    template:
                        '<button :disabled="disabled" @click="$emit(\'click\', $event)"><slot /></button>',
                },
            },
        },
    })

    return {
        wrapper,
        router,
        authStore,
    }
}

function configureOrganizations(authStore) {
    authStore.organizations = [
        {
            id: 10,

            name: 'Escritório A',

            slug: 'escritorio-a',
        },

        {
            id: 20,

            name: 'Escritório B',

            slug: 'escritorio-b',
        },
    ]
}

describe('OrganizationSelectPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('renderiza título da página', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.get('#organization-select-title').text()).toBe('Selecione o escritório')
    })

    it('renderiza organizações disponíveis', async () => {
        const { wrapper, authStore } = await mountPage()

        configureOrganizations(authStore)

        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('Escritório A')

        expect(wrapper.text()).toContain('Escritório B')

        expect(wrapper.text()).toContain('escritorio-a')

        expect(wrapper.text()).toContain('escritorio-b')
    })

    it('exibe estado vazio sem organizações', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Nenhuma organização disponível')

        expect(wrapper.text()).toContain('Sua conta não possui vínculo ativo com um escritório.')
    })

    it('seleciona organização e redireciona para dashboard', async () => {
        const { wrapper, router, authStore } = await mountPage()

        configureOrganizations(authStore)

        const selectSpy = vi.spyOn(authStore, 'selectOrganization').mockResolvedValue({
            organization: {
                id: 10,
                slug: 'escritorio-a',
            },
        })

        await wrapper.vm.$nextTick()

        const buttons = wrapper.findAll('.organization-select-page__organization')

        await buttons[0].trigger('click')

        await vi.waitFor(() => {
            expect(selectSpy).toHaveBeenCalledWith({
                id: 10,

                name: 'Escritório A',

                slug: 'escritorio-a',
            })

            expect(router.currentRoute.value.name).toBe('dashboard')
        })
    })

    it('preserva destino original após seleção', async () => {
        const { wrapper, router, authStore } = await mountPage(
            '/organizations/select?redirect=/clients',
        )

        configureOrganizations(authStore)

        vi.spyOn(authStore, 'selectOrganization').mockResolvedValue({})

        await wrapper.vm.$nextTick()

        await wrapper.findAll('.organization-select-page__organization')[0].trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.fullPath).toBe('/clients')
        })
    })

    it('ignora redirect externo', async () => {
        const { wrapper, router, authStore } = await mountPage(
            '/organizations/select?redirect=//evil.example',
        )

        configureOrganizations(authStore)

        vi.spyOn(authStore, 'selectOrganization').mockResolvedValue({})

        await wrapper.vm.$nextTick()

        await wrapper.findAll('.organization-select-page__organization')[0].trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('dashboard')
        })
    })

    it('exibe erro quando seleção falha', async () => {
        const { wrapper, authStore } = await mountPage()

        configureOrganizations(authStore)

        vi.spyOn(authStore, 'selectOrganization').mockRejectedValue(new Error('Forbidden'))

        await wrapper.vm.$nextTick()

        await wrapper.findAll('.organization-select-page__organization')[0].trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível acessar o escritório selecionado. Tente novamente.',
            )
        })
    })

    it('executa logout e retorna para login', async () => {
        const { wrapper, router, authStore } = await mountPage()

        vi.spyOn(authStore, 'logout').mockResolvedValue()

        const logoutButton = wrapper.findAll('button').find((button) => button.text() === 'Sair')

        expect(logoutButton).toBeTruthy()

        await logoutButton.trigger('click')

        await vi.waitFor(() => {
            expect(authStore.logout).toHaveBeenCalledTimes(1)

            expect(router.currentRoute.value.name).toBe('login')
        })
    })
})
