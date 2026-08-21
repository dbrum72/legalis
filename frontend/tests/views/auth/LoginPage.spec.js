import { beforeEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import LoginPage from '@/views/auth/LoginPage.vue'

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

function createTestRouter(initialRoute = '/login') {
    const router = createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/',

                name: 'home',

                component: {
                    template: '<div>Home</div>',
                },
            },

            {
                path: '/login',

                name: 'login',

                component: LoginPage,
            },

            {
                path: '/register',

                name: 'register',

                component: {
                    template: '<div>Cadastro</div>',
                },
            },

            {
                path: '/login',

                name: 'login',

                component: LoginPage,
            },

            {
                path: '/register',

                name: 'register',

                component: {
                    template: '<div>Cadastro</div>',
                },
            },

            {
                path: '/organizations/select',

                name: 'organizations.select',

                component: {
                    template: '<div>Selecionar organização</div>',
                },
            },

            {
                path: '/',

                name: 'dashboard',

                component: {
                    template: '<div>Dashboard</div>',
                },
            },

            {
                path: '/destino',

                name: 'destination',

                component: {
                    template: '<div>Destino</div>',
                },
            },
        ],
    })

    return {
        router,
        initialRoute,
    }
}

async function mountPage(initialRoute = '/login') {
    const pinia = createPinia()

    setActivePinia(pinia)

    const { router } = createTestRouter(initialRoute)

    await router.push(initialRoute)

    await router.isReady()

    const wrapper = mount(LoginPage, {
        global: {
            plugins: [pinia, router],
        },
    })

    return {
        wrapper,
        router,

        authStore: useAuthStore(),
    }
}

function mockLoginWithContext(authStore) {
    return vi.spyOn(authStore, 'login').mockImplementation(async () => {
        authStore.token = 'jwt-token'

        authStore.user = {
            id: 1,

            name: 'Super Admin',

            email: 'super-admin@legalis.local',
        }

        authStore.organizations = [
            {
                id: 10,

                name: 'Escritório Legalis',

                slug: 'escritorio-legalis',
            },
        ]

        authStore.organization = {
            id: 10,

            name: 'Escritório Legalis',

            slug: 'escritorio-legalis',
        }

        authStore.roles = ['super-admin']

        authStore.permissions = ['clients.view']

        authStore.contextLoaded = true

        return {
            access_token: 'jwt-token',
        }
    })
}

describe('LoginPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('renderiza formulário de login', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.get('form').exists()).toBe(true)

        expect(wrapper.get('#login-email').exists()).toBe(true)

        expect(wrapper.get('#login-password').exists()).toBe(true)
    })

    it('renderiza título da página', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.get('#login-title').text()).toBe('Acesse sua conta')
    })

    it('não submete quando email está vazio', async () => {
        const { wrapper, authStore } = await mountPage()

        const loginSpy = vi.spyOn(authStore, 'login')

        await wrapper.get('#login-password').setValue('senha')

        await wrapper.get('form').trigger('submit')

        expect(loginSpy).not.toHaveBeenCalled()

        expect(wrapper.text()).toContain('Informe seu e-mail.')
    })

    it('não submete quando senha está vazia', async () => {
        const { wrapper, authStore } = await mountPage()

        const loginSpy = vi.spyOn(authStore, 'login')

        await wrapper.get('#login-email').setValue('admin@legalis.local')

        await wrapper.get('form').trigger('submit')

        expect(loginSpy).not.toHaveBeenCalled()

        expect(wrapper.text()).toContain('Informe sua senha.')
    })

    it('executa login com email normalizado', async () => {
        const { wrapper, authStore } = await mountPage()

        const loginSpy = mockLoginWithContext(authStore)

        await wrapper.get('#login-email').setValue('  admin@legalis.local  ')

        await wrapper.get('#login-password').setValue('senha')

        await wrapper.get('form').trigger('submit')

        expect(loginSpy).toHaveBeenCalledWith({
            email: 'admin@legalis.local',

            password: 'senha',
        })
    })

    it('redireciona para dashboard após login com contexto carregado e sem redirect', async () => {
        const { wrapper, router, authStore } = await mountPage()

        mockLoginWithContext(authStore)

        await wrapper.get('#login-email').setValue('admin@legalis.local')

        await wrapper.get('#login-password').setValue('senha')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('dashboard')
        })
    })

    it('redireciona para destino original após login com contexto carregado', async () => {
        const { wrapper, router, authStore } = await mountPage('/login?redirect=/destino')

        mockLoginWithContext(authStore)

        await wrapper.get('#login-email').setValue('admin@legalis.local')

        await wrapper.get('#login-password').setValue('senha')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.fullPath).toBe('/destino')
        })
    })

    it('redireciona para seleção de organização quando login não carrega contexto', async () => {
        const { wrapper, router, authStore } = await mountPage('/login?redirect=/destino')

        vi.spyOn(authStore, 'login').mockImplementation(async () => {
            authStore.token = 'jwt-token'

            authStore.user = {
                id: 1,

                name: 'Super Admin',
            }

            authStore.organizations = [
                {
                    id: 10,

                    name: 'Organização A',

                    slug: 'org-a',
                },

                {
                    id: 20,

                    name: 'Organização B',

                    slug: 'org-b',
                },
            ]

            authStore.contextLoaded = false

            return {
                access_token: 'jwt-token',
            }
        })

        await wrapper.get('#login-email').setValue('admin@legalis.local')

        await wrapper.get('#login-password').setValue('senha')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('organizations.select')

            expect(router.currentRoute.value.query.redirect).toBe('/destino')
        })
    })

    it('ignora redirect externo', async () => {
        const { wrapper, router, authStore } = await mountPage('/login?redirect=//evil.example')

        mockLoginWithContext(authStore)

        await wrapper.get('#login-email').setValue('admin@legalis.local')

        await wrapper.get('#login-password').setValue('senha')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('dashboard')
        })
    })

    it('exibe erro de credenciais inválidas', async () => {
        const { wrapper, authStore } = await mountPage()

        vi.spyOn(authStore, 'login').mockRejectedValue({
            response: {
                status: 403,

                data: {
                    msg: 'Usuário e/ou senha inválidos.',
                },
            },
        })

        await wrapper.get('#login-email').setValue('admin@legalis.local')

        await wrapper.get('#login-password').setValue('errada')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Usuário e/ou senha inválidos.')
        })
    })

    it('exibe erros 422 retornados pela api', async () => {
        const { wrapper, authStore } = await mountPage()

        vi.spyOn(authStore, 'login').mockRejectedValue({
            response: {
                status: 422,

                data: {
                    errors: {
                        email: ['O e-mail informado é inválido.'],
                    },
                },
            },
        })

        await wrapper.get('#login-email').setValue('email-invalido')

        await wrapper.get('#login-password').setValue('senha')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('O e-mail informado é inválido.')
        })
    })

    it('exibe erro genérico para falha inesperada', async () => {
        const { wrapper, authStore } = await mountPage()

        vi.spyOn(authStore, 'login').mockRejectedValue({
            response: {
                status: 500,
            },
        })

        await wrapper.get('#login-email').setValue('admin@legalis.local')

        await wrapper.get('#login-password').setValue('senha')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível acessar sua conta. Tente novamente.')
        })
    })
})
