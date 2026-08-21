import { beforeEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import RegisterPage from '@/views/auth/RegisterPage.vue'

import { useAuthStore } from '@/stores/auth.js'

vi.mock('@/api/auth.js', () => ({
    context: vi.fn(),
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    refresh: vi.fn(),
    register: vi.fn(),
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

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/register',

                name: 'register',

                component: RegisterPage,
            },

            {
                path: '/login',

                name: 'login',

                component: {
                    template: '<div>Login</div>',
                },
            },

            {
                path: '/',

                name: 'home',

                component: {
                    template: '<div>Home</div>',
                },
            },

            {
                path: '/dashboard',

                name: 'dashboard',

                component: {
                    template: '<div>Dashboard</div>',
                },
            },
        ],
    })
}

async function mountPage() {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push('/register')

    await router.isReady()

    const wrapper = mount(RegisterPage, {
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

function mockSuccessfulRegister(authStore) {
    return vi.spyOn(authStore, 'register').mockImplementation(async () => {
        authStore.token = 'register-token'

        authStore.user = {
            id: 15,
            name: 'João Silva',
            email: 'joao@silva.test',
        }

        authStore.organizations = [
            {
                id: 30,
                name: 'Silva Advocacia',
                slug: 'silva-advocacia',
            },
        ]

        authStore.organization = {
            id: 30,
            name: 'Silva Advocacia',
            slug: 'silva-advocacia',
        }

        authStore.roles = ['socio-administrador']

        authStore.permissions = ['clients.view']

        authStore.contextLoaded = true

        return {
            access_token: 'register-token',
        }
    })
}

async function fillValidForm(wrapper) {
    await wrapper.get('#register-name').setValue('João Silva')

    await wrapper.get('#register-organization-name').setValue('Silva Advocacia')

    await wrapper.get('#register-email').setValue('joao@silva.test')

    await wrapper.get('#register-password').setValue('Password123!')

    await wrapper.get('#register-password-confirmation').setValue('Password123!')
}

describe('RegisterPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('renderiza formulario de cadastro', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.get('form').exists()).toBe(true)

        expect(wrapper.get('#register-name').exists()).toBe(true)

        expect(wrapper.get('#register-organization-name').exists()).toBe(true)

        expect(wrapper.get('#register-email').exists()).toBe(true)

        expect(wrapper.get('#register-password').exists()).toBe(true)

        expect(wrapper.get('#register-password-confirmation').exists()).toBe(true)
    })

    it('renderiza titulo da pagina', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.get('#register-title').text()).toBe('Crie sua conta')
    })

    it('nao submete formulario vazio', async () => {
        const { wrapper, authStore } = await mountPage()

        const registerSpy = vi.spyOn(authStore, 'register')

        await wrapper.get('form').trigger('submit')

        expect(registerSpy).not.toHaveBeenCalled()

        expect(wrapper.text()).toContain('Informe seu nome.')

        expect(wrapper.text()).toContain('Informe o nome do escritório.')

        expect(wrapper.text()).toContain('Informe seu e-mail.')

        expect(wrapper.text()).toContain('Informe uma senha.')
    })

    it('valida confirmacao da senha antes de enviar', async () => {
        const { wrapper, authStore } = await mountPage()

        const registerSpy = vi.spyOn(authStore, 'register')

        await fillValidForm(wrapper)

        await wrapper.get('#register-password-confirmation').setValue('OutraSenha123!')

        await wrapper.get('form').trigger('submit')

        expect(registerSpy).not.toHaveBeenCalled()

        expect(wrapper.text()).toContain('As senhas não coincidem.')
    })

    it('executa cadastro com dados normalizados', async () => {
        const { wrapper, authStore } = await mountPage()

        const registerSpy = mockSuccessfulRegister(authStore)

        await fillValidForm(wrapper)

        await wrapper.get('#register-name').setValue('  João Silva  ')

        await wrapper.get('#register-organization-name').setValue('  Silva Advocacia  ')

        await wrapper.get('#register-email').setValue('  joao@silva.test  ')

        await wrapper.get('form').trigger('submit')

        expect(registerSpy).toHaveBeenCalledWith({
            name: 'João Silva',

            organization_name: 'Silva Advocacia',

            email: 'joao@silva.test',

            password: 'Password123!',

            password_confirmation: 'Password123!',
        })
    })

    it('redireciona para dashboard apos cadastro', async () => {
        const { wrapper, router, authStore } = await mountPage()

        mockSuccessfulRegister(authStore)

        await fillValidForm(wrapper)

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('dashboard')
        })
    })

    it('exibe erros 422 retornados pela api', async () => {
        const { wrapper, authStore } = await mountPage()

        vi.spyOn(authStore, 'register').mockRejectedValue({
            response: {
                status: 422,

                data: {
                    errors: {
                        email: ['Este e-mail já está cadastrado.'],

                        organization_name: ['O nome da organização é obrigatório.'],
                    },
                },
            },
        })

        await fillValidForm(wrapper)

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Este e-mail já está cadastrado.')

            expect(wrapper.text()).toContain('O nome da organização é obrigatório.')
        })
    })

    it('exibe erro generico quando cadastro falha', async () => {
        const { wrapper, authStore } = await mountPage()

        vi.spyOn(authStore, 'register').mockRejectedValue(new Error('Network error'))

        await fillValidForm(wrapper)

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível criar sua conta. Tente novamente.')
        })
    })

    it('possui acesso para pagina de login', async () => {
        const { wrapper } = await mountPage()

        const link = wrapper.get('[data-testid="register-login-link"]')

        expect(link.attributes('href')).toBe('/login')

        expect(link.text()).toContain('Entrar')
    })
})
