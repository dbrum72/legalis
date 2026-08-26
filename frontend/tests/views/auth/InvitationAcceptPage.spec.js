import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import { createMemoryHistory, createRouter } from 'vue-router'

vi.mock('@/api/organization-invitations.js', () => ({
    getInvitationAcceptance: vi.fn(),

    acceptOrganizationInvitation: vi.fn(),
}))

vi.mock('@/api/auth-token.js', () => ({
    getAccessToken: vi.fn(() => null),

    setAccessToken: vi.fn(),

    removeAccessToken: vi.fn(),
}))

import {
    acceptOrganizationInvitation,
    getInvitationAcceptance,
} from '@/api/organization-invitations.js'

import { useAuthStore } from '@/stores/auth.js'

import InvitationAcceptPage from '@/views/auth/InvitationAcceptPage.vue'

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/invitations/accept/:token',

                name: 'invitations.accept',

                component: InvitationAcceptPage,
            },

            {
                path: '/login',

                name: 'login',

                component: {
                    template: '<div>Login</div>',
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

async function mountPage(token = 'token-de-teste') {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push(`/invitations/accept/${token}`)

    await router.isReady()

    const wrapper = mount(InvitationAcceptPage, {
        global: {
            plugins: [pinia, router],

            stubs: {
                AppLogo: {
                    template: '<div class="app-logo-stub">Legalis</div>',
                },

                AppCard: {
                    template: '<section><slot /></section>',
                },

                AppIcon: {
                    template: '<span class="app-icon-stub" />',
                },
            },
        },
    })

    const authStore = useAuthStore(pinia)

    const applyAuthPayloadSpy = vi.spyOn(authStore, 'applyAuthPayload')

    const initializeContextSpy = vi.spyOn(authStore, 'initializeContext').mockResolvedValue(null)

    await flushPromises()

    return {
        wrapper,
        router,
        authStore,
        applyAuthPayloadSpy,
        initializeContextSpy,
    }
}

function organization() {
    return {
        id: 10,

        name: 'Escritório Legalis',

        slug: 'escritorio-legalis',
    }
}

function newUserInvitation() {
    return {
        email: 'novo@example.com',

        role: 'advogado-junior',

        expires_at: '2026-08-22T20:00:00.000000Z',

        registration_required: true,

        organization: organization(),
    }
}

function existingUserInvitation() {
    return {
        ...newUserInvitation(),

        email: 'existente@example.com',

        registration_required: false,
    }
}

function newUserAcceptance() {
    return {
        access_token: 'jwt-do-novo-usuario',

        token_type: 'bearer',

        expires_in: 3600,

        user: {
            id: 20,

            name: 'Novo Usuário',

            email: 'novo@example.com',
        },

        organizations: [organization()],

        organization: organization(),

        role: 'advogado-junior',
    }
}

function existingUserAcceptance() {
    return {
        user: {
            id: 21,

            name: 'Usuário Existente',

            email: 'existente@example.com',
        },

        organization: organization(),

        role: 'advogado-junior',
    }
}

describe('InvitationAcceptPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('carrega convite pelo token da rota', async () => {
        getInvitationAcceptance.mockResolvedValue(newUserInvitation())

        await mountPage('abc123')

        expect(getInvitationAcceptance).toHaveBeenCalledWith('abc123')
    })

    it('renderiza dados do convite', async () => {
        getInvitationAcceptance.mockResolvedValue(newUserInvitation())

        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Escritório Legalis')

        expect(wrapper.text()).toContain('novo@example.com')

        expect(wrapper.text()).toContain('Advogado Junior')
    })

    it('renderiza cadastro quando usuário ainda não existe', async () => {
        getInvitationAcceptance.mockResolvedValue(newUserInvitation())

        const { wrapper } = await mountPage()

        expect(wrapper.find('#invitation-name').exists()).toBe(true)

        expect(wrapper.find('#invitation-password').exists()).toBe(true)

        expect(wrapper.find('#invitation-password-confirmation').exists()).toBe(true)
    })

    it('não renderiza cadastro para usuário existente', async () => {
        getInvitationAcceptance.mockResolvedValue(existingUserInvitation())

        const { wrapper } = await mountPage()

        expect(wrapper.find('#invitation-name').exists()).toBe(false)

        expect(wrapper.text()).toContain('Sua conta já existe no Legalis.')
    })

    it('envia nome senha e confirmação para usuário novo', async () => {
        getInvitationAcceptance.mockResolvedValue(newUserInvitation())

        acceptOrganizationInvitation.mockResolvedValue(newUserAcceptance())

        const { wrapper } = await mountPage('token-novo')

        await wrapper.get('#invitation-name').setValue('  Novo Usuário  ')

        await wrapper.get('#invitation-password').setValue('password123')

        await wrapper.get('#invitation-password-confirmation').setValue('password123')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(acceptOrganizationInvitation).toHaveBeenCalledWith('token-novo', {
            name: 'Novo Usuário',

            password: 'password123',

            password_confirmation: 'password123',
        })
    })

    it('envia payload vazio para usuário existente', async () => {
        getInvitationAcceptance.mockResolvedValue(existingUserInvitation())

        acceptOrganizationInvitation.mockResolvedValue(existingUserAcceptance())

        const { wrapper } = await mountPage('token-existente')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(acceptOrganizationInvitation).toHaveBeenCalledWith('token-existente', {})
    })

    it('exibe sucesso após aceite de usuário existente', async () => {
        getInvitationAcceptance.mockResolvedValue(existingUserInvitation())

        acceptOrganizationInvitation.mockResolvedValue(existingUserAcceptance())

        const { wrapper } = await mountPage()

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(wrapper.text()).toContain('Convite aceito')

        expect(wrapper.text()).toContain('Seu acesso a')

        expect(wrapper.text()).toContain('Escritório Legalis')
    })

    it('autentica novo usuário e redireciona automaticamente ao dashboard', async () => {
        getInvitationAcceptance.mockResolvedValue(newUserInvitation())

        const acceptance = newUserAcceptance()

        acceptOrganizationInvitation.mockResolvedValue(acceptance)

        const { wrapper, router, authStore, applyAuthPayloadSpy, initializeContextSpy } =
            await mountPage('token-novo')

        await wrapper.get('#invitation-name').setValue('Novo Usuário')

        await wrapper.get('#invitation-password').setValue('password123')

        await wrapper.get('#invitation-password-confirmation').setValue('password123')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(applyAuthPayloadSpy).toHaveBeenCalledWith(acceptance)

        expect(authStore.token).toBe('jwt-do-novo-usuario')

        expect(authStore.user).toEqual(acceptance.user)

        expect(authStore.organizations).toEqual(acceptance.organizations)

        expect(initializeContextSpy).toHaveBeenCalledOnce()

        expect(router.currentRoute.value.name).toBe('dashboard')
    })

    it('mantém usuário existente sem autenticação e o encaminha ao login', async () => {
        getInvitationAcceptance.mockResolvedValue(existingUserInvitation())

        acceptOrganizationInvitation.mockResolvedValue(existingUserAcceptance())

        const { wrapper, router, authStore, applyAuthPayloadSpy, initializeContextSpy } =
            await mountPage('token-existente')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(applyAuthPayloadSpy).not.toHaveBeenCalled()

        expect(initializeContextSpy).not.toHaveBeenCalled()

        expect(authStore.isAuthenticated).toBe(false)

        const loginButton = wrapper
            .findAll('button')
            .find((button) => button.text() === 'Entrar no Legalis')

        expect(loginButton).toBeTruthy()

        await loginButton.trigger('click')

        await flushPromises()

        expect(router.currentRoute.value.name).toBe('login')
    })

    it('exibe convite não encontrado para 404', async () => {
        getInvitationAcceptance.mockRejectedValue({
            response: {
                status: 404,
            },
        })

        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Convite não encontrado')
    })

    it('exibe convite indisponível para 410', async () => {
        getInvitationAcceptance.mockRejectedValue({
            response: {
                status: 410,
            },
        })

        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Convite indisponível')

        expect(wrapper.text()).toContain('expirou, foi revogado ou já foi utilizado')
    })

    it('exibe erro genérico quando consulta falha', async () => {
        getInvitationAcceptance.mockRejectedValue({
            response: {
                status: 500,
            },
        })

        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Não foi possível carregar o convite')
    })

    it('exibe erros 422 retornados no aceite', async () => {
        getInvitationAcceptance.mockResolvedValue(newUserInvitation())

        acceptOrganizationInvitation.mockRejectedValue({
            response: {
                status: 422,

                data: {
                    errors: {
                        password: ['A senha deve possuir pelo menos 8 caracteres.'],
                    },
                },
            },
        })

        const { wrapper } = await mountPage()

        await wrapper.get('#invitation-name').setValue('Novo Usuário')

        await wrapper.get('#invitation-password').setValue('123')

        await wrapper.get('#invitation-password-confirmation').setValue('123')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(wrapper.text()).toContain('A senha deve possuir pelo menos 8 caracteres.')

        expect(wrapper.text()).toContain('Verifique os campos informados.')
    })

    it('transforma 410 durante aceite em estado de convite indisponível', async () => {
        getInvitationAcceptance.mockResolvedValue(existingUserInvitation())

        acceptOrganizationInvitation.mockRejectedValue({
            response: {
                status: 410,
            },
        })

        const { wrapper } = await mountPage()

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(wrapper.text()).toContain('Convite indisponível')
    })

    it('exibe erro genérico quando aceite falha inesperadamente', async () => {
        getInvitationAcceptance.mockResolvedValue(existingUserInvitation())

        acceptOrganizationInvitation.mockRejectedValue({
            response: {
                status: 500,
            },
        })

        const { wrapper } = await mountPage()

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(wrapper.text()).toContain('Não foi possível aceitar o convite. Tente novamente.')
    })
})
