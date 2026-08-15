import { beforeEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/components/forms', () => ({
    AppSelect: {
        name: 'AppSelect',

        props: {
            modelValue: {
                type: [String, Number, Boolean, Object],

                default: null,
            },

            options: {
                type: Array,
                default: () => [],
            },

            disabled: {
                type: Boolean,
                default: false,
            },

            name: {
                type: String,
                default: '',
            },

            label: {
                type: String,
                default: '',
            },

            optionLabel: {
                type: String,
                default: 'label',
            },

            optionValue: {
                type: String,
                default: 'value',
            },
        },

        emits: ['update:modelValue'],

        template: `
            <div
                class="app-select-stub"
                :data-model-value="modelValue"
            />
        `,
    },
}))

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

import HeaderBar from '@/components/layout/HeaderBar/index.vue'

import { AppSelect } from '@/components/forms'

import { useAuthStore } from '@/stores/auth.js'

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/',

                name: 'dashboard',

                component: {
                    template: '<div>Dashboard</div>',
                },
            },

            {
                path: '/organizations/select',

                name: 'organizations.select',

                component: {
                    template: '<div>Organizações</div>',
                },
            },

            {
                path: '/login',

                name: 'login',

                component: {
                    template: '<div>Login</div>',
                },
            },

            {
                path: '/clients',

                name: 'clients',

                component: {
                    template: '<div>Clientes</div>',
                },
            },
        ],
    })
}

async function mountComponent() {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push('/clients')

    await router.isReady()

    const authStore = useAuthStore()

    authStore.token = 'jwt-token'

    authStore.user = {
        id: 1,

        name: 'Super Admin',

        email: 'super-admin@legalis.local',
    }

    const wrapper = mount(HeaderBar, {
        global: {
            plugins: [pinia, router],

            stubs: {
                AppBreadcrumb: {
                    name: 'AppBreadcrumb',

                    template: '<nav>Breadcrumb</nav>',
                },

                AppButton: {
                    name: 'AppButton',

                    props: {
                        loading: {
                            type: Boolean,
                            default: false,
                        },

                        disabled: {
                            type: Boolean,
                            default: false,
                        },
                    },

                    emits: ['click'],

                    template: `
                                <button
                                    :disabled="disabled"
                                    @click="$emit('click', $event)"
                                >
                                    <slot />
                                </button>
                            `,
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

function configureSingleOrganization(authStore) {
    authStore.organizations = [
        {
            id: 10,

            name: 'Escritório A',

            slug: 'escritorio-a',
        },
    ]

    authStore.organization = {
        id: 10,

        name: 'Escritório A',

        slug: 'escritorio-a',
    }

    authStore.contextLoaded = true
}

function configureMultipleOrganizations(authStore) {
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

    authStore.organization = {
        id: 10,

        name: 'Escritório A',

        slug: 'escritorio-a',
    }

    authStore.contextLoaded = true
}

describe('HeaderBar', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('renderiza usuário autenticado', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Super Admin')

        expect(wrapper.text()).toContain('Sair')
    })

    it('exibe nome da organização quando existe apenas uma', async () => {
        const { wrapper, authStore } = await mountComponent()

        configureSingleOrganization(authStore)

        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('Escritório A')

        expect(wrapper.find('.app-select-stub').exists()).toBe(false)
    })

    it('renderiza seletor quando existem várias organizações', async () => {
        const { wrapper, authStore } = await mountComponent()

        configureMultipleOrganizations(authStore)

        await wrapper.vm.$nextTick()

        const select = wrapper.findComponent(AppSelect)

        expect(select.exists()).toBe(true)

        expect(wrapper.find('.app-select-stub').exists()).toBe(true)

        expect(select.props('modelValue')).toBe('escritorio-a')

        expect(select.props('options')).toEqual([
            {
                label: 'Escritório A',

                value: 'escritorio-a',
            },

            {
                label: 'Escritório B',

                value: 'escritorio-b',
            },
        ])

        expect(select.props('optionLabel')).toBe('label')

        expect(select.props('optionValue')).toBe('value')
    })

    it('troca organização e retorna ao dashboard', async () => {
        const { wrapper, router, authStore } = await mountComponent()

        configureMultipleOrganizations(authStore)

        const selectSpy = vi
            .spyOn(authStore, 'selectOrganization')
            .mockImplementation(async (tenant) => {
                authStore.organization = authStore.organizations.find(
                    (organization) => organization.slug === tenant,
                )

                authStore.contextLoaded = true

                return {
                    organization: authStore.organization,
                }
            })

        await wrapper.vm.$nextTick()

        const select = wrapper.findComponent(AppSelect)

        expect(select.exists()).toBe(true)

        select.vm.$emit('update:modelValue', 'escritorio-b')

        await vi.waitFor(() => {
            expect(selectSpy).toHaveBeenCalledWith('escritorio-b')

            expect(router.currentRoute.value.name).toBe('dashboard')
        })
    })

    it('não troca quando tenant informado já é o atual', async () => {
        const { wrapper, authStore } = await mountComponent()

        configureMultipleOrganizations(authStore)

        const selectSpy = vi.spyOn(authStore, 'selectOrganization')

        await wrapper.vm.$nextTick()

        const select = wrapper.findComponent(AppSelect)

        expect(select.exists()).toBe(true)

        select.vm.$emit('update:modelValue', 'escritorio-a')

        await wrapper.vm.$nextTick()

        expect(selectSpy).not.toHaveBeenCalled()
    })

    it('redireciona para seleção quando troca de organização falha', async () => {
        const { wrapper, router, authStore } = await mountComponent()

        configureMultipleOrganizations(authStore)

        vi.spyOn(authStore, 'selectOrganization').mockRejectedValue(new Error('Forbidden'))

        await wrapper.vm.$nextTick()

        const select = wrapper.findComponent(AppSelect)

        expect(select.exists()).toBe(true)

        select.vm.$emit('update:modelValue', 'escritorio-b')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('organizations.select')
        })
    })

    it('executa logout e retorna ao login', async () => {
        const { wrapper, router, authStore } = await mountComponent()

        configureSingleOrganization(authStore)

        vi.spyOn(authStore, 'logout').mockResolvedValue()

        await wrapper.vm.$nextTick()

        const logoutButton = wrapper.findAll('button').find((button) => button.text() === 'Sair')

        expect(logoutButton).toBeTruthy()

        await logoutButton.trigger('click')

        await vi.waitFor(() => {
            expect(authStore.logout).toHaveBeenCalledTimes(1)

            expect(router.currentRoute.value.name).toBe('login')
        })
    })
})
