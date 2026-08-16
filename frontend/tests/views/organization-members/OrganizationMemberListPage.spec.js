import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

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

vi.mock('@/api/organization-members.js', () => ({
    listOrganizationMembers: vi.fn(),
    updateOrganizationMemberRole: vi.fn(),
    updateOrganizationMemberStatus: vi.fn(),
}))

vi.mock('@/api/organization-roles.js', () => ({
    listOrganizationRoles: vi.fn(),
}))

vi.mock('@/api/organization-invitations.js', () => ({
    createOrganizationInvitation: vi.fn(),
    getInvitationAcceptance: vi.fn(),
    acceptOrganizationInvitation: vi.fn(),
}))

import OrganizationMemberListPage from '@/views/organization-members/OrganizationMemberListPage.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useOrganizationInvitationsStore } from '@/stores/organization-invitations.js'
import { useOrganizationMembersStore } from '@/stores/organization-members.js'
import { useOrganizationRolesStore } from '@/stores/organization-roles.js'

function defaultMembers() {
    return [
        {
            id: 1,
            name: 'Administrador',
            email: 'admin@legalis.local',
            role: 'administrador',
            status: 'active',
        },
        {
            id: 2,
            name: 'Advogado Júnior',
            email: 'junior@legalis.local',
            role: 'advogado-junior',
            status: 'inactive',
        },
    ]
}

function defaultRoles() {
    return [
        {
            id: 1,
            name: 'administrador',
        },
        {
            id: 2,
            name: 'advogado-junior',
        },
        {
            id: 3,
            name: 'secretaria',
        },
    ]
}

async function mountPage({
    permissions = [],
    members = defaultMembers(),
    roles = defaultRoles(),
    membersError = null,
    rolesError = null,
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()

    const invitationsStore = useOrganizationInvitationsStore()

    const membersStore = useOrganizationMembersStore()

    const rolesStore = useOrganizationRolesStore()

    authStore.permissions = permissions

    vi.spyOn(membersStore, 'fetchMembers').mockImplementation(async () => {
        if (membersError) {
            throw membersError
        }

        membersStore.members = members

        return members
    })

    vi.spyOn(rolesStore, 'fetchRoles').mockImplementation(async () => {
        if (rolesError) {
            throw rolesError
        }

        rolesStore.roles = roles

        return roles
    })

    const wrapper = mount(OrganizationMemberListPage, {
        attachTo: document.body,

        global: {
            plugins: [pinia],
        },
    })

    await flushPromises()

    return {
        wrapper,
        authStore,
        invitationsStore,
        membersStore,
        rolesStore,
    }
}

function findButton(label, root = document) {
    return Array.from(root.querySelectorAll('button')).find(
        (button) => button.textContent.trim() === label,
    )
}

function findButtons(label, root = document) {
    return Array.from(root.querySelectorAll('button')).filter(
        (button) => button.textContent.trim() === label,
    )
}

async function fillInvitationForm(
    wrapper,
    {
        email = 'novo@legalis.local',

        role = 'advogado-junior',
    } = {},
) {
    const emailInput = document.querySelector('#invitation-email')

    const roleSelect = document.querySelector('#invitation-role')

    expect(emailInput).not.toBeNull()

    expect(roleSelect).not.toBeNull()

    emailInput.value = email

    emailInput.dispatchEvent(
        new Event('input', {
            bubbles: true,
        }),
    )

    roleSelect.value = role

    roleSelect.dispatchEvent(
        new Event('change', {
            bubbles: true,
        }),
    )

    await wrapper.vm.$nextTick()
}

describe('OrganizationMemberListPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega membros e funções ao montar', async () => {
        const { membersStore, rolesStore } = await mountPage()

        expect(membersStore.fetchMembers).toHaveBeenCalledTimes(1)

        expect(rolesStore.fetchRoles).toHaveBeenCalledTimes(1)
    })

    it('renderiza título e descrição da página', async () => {
        await mountPage()

        expect(document.body.textContent).toContain('Equipe')

        expect(document.body.textContent).toContain(
            'Gerencie os membros, funções e acessos do escritório.',
        )
    })

    it('renderiza membros retornados pela store', async () => {
        await mountPage()

        expect(document.body.textContent).toContain('Administrador')

        expect(document.body.textContent).toContain('admin@legalis.local')

        expect(document.body.textContent).toContain('Advogado Júnior')

        expect(document.body.textContent).toContain('junior@legalis.local')
    })

    it('formata função do membro', async () => {
        await mountPage()

        expect(document.body.textContent).toContain('Advogado Junior')
    })

    it('renderiza status dos membros', async () => {
        await mountPage()

        expect(document.body.textContent).toContain('Ativo')

        expect(document.body.textContent).toContain('Inativo')
    })

    it('renderiza estado vazio quando não existem membros', async () => {
        await mountPage({
            members: [],
        })

        expect(document.body.textContent).toContain('Nenhum membro encontrado.')
    })

    it('exibe erro quando carregamento dos membros falha', async () => {
        await mountPage({
            membersError: new Error('Falha ao carregar membros'),
        })

        expect(document.body.textContent).toContain('Não foi possível carregar os dados da equipe.')
    })

    it('exibe erro quando carregamento das funções falha', async () => {
        await mountPage({
            rolesError: new Error('Falha ao carregar funções'),
        })

        expect(document.body.textContent).toContain('Não foi possível carregar os dados da equipe.')
    })

    it('não mostra Convidar membro sem organization-members.invite', async () => {
        await mountPage({
            permissions: ['organization-members.view'],
        })

        expect(findButton('Convidar membro')).toBeUndefined()
    })

    it('mostra Convidar membro com organization-members.invite', async () => {
        await mountPage({
            permissions: ['organization-members.view', 'organization-members.invite'],
        })

        expect(findButton('Convidar membro')).toBeTruthy()
    })

    it('abre diálogo de convite', async () => {
        const { wrapper } = await mountPage({
            permissions: ['organization-members.invite'],
        })

        const button = findButton('Convidar membro')

        expect(button).toBeTruthy()

        button.click()

        await wrapper.vm.$nextTick()

        expect(document.querySelector('.app-dialog')).not.toBeNull()

        expect(document.body.textContent).toContain('Convidar membro')

        expect(document.querySelector('#invitation-email')).not.toBeNull()

        expect(document.querySelector('#invitation-role')).not.toBeNull()
    })

    it('cancela diálogo de convite', async () => {
        const { wrapper } = await mountPage({
            permissions: ['organization-members.invite'],
        })

        findButton('Convidar membro').click()

        await wrapper.vm.$nextTick()

        const dialog = document.querySelector('.app-dialog')

        expect(dialog).not.toBeNull()

        const cancelButton = findButton('Cancelar', dialog)

        expect(cancelButton).toBeTruthy()

        cancelButton.click()

        await wrapper.vm.$nextTick()

        expect(document.querySelector('.app-dialog')).toBeNull()
    })

    it('envia convite com email e função', async () => {
        const { wrapper, invitationsStore } = await mountPage({
            permissions: ['organization-members.invite'],
        })

        const createSpy = vi.spyOn(invitationsStore, 'create').mockResolvedValue({
            id: 10,
        })

        findButton('Convidar membro').click()

        await wrapper.vm.$nextTick()

        await fillInvitationForm(wrapper)

        const submitButton = findButton('Enviar convite')

        expect(submitButton).toBeTruthy()

        submitButton.click()

        await flushPromises()

        expect(createSpy).toHaveBeenCalledTimes(1)

        expect(createSpy).toHaveBeenCalledWith({
            email: 'novo@legalis.local',

            role: 'advogado-junior',
        })

        expect(document.querySelector('.app-dialog')).toBeNull()
    })

    it('exibe erros 422 ao enviar convite', async () => {
        const { wrapper, invitationsStore } = await mountPage({
            permissions: ['organization-members.invite'],
        })

        vi.spyOn(invitationsStore, 'create').mockRejectedValue({
            response: {
                status: 422,

                data: {
                    errors: {
                        email: ['Este e-mail já possui convite pendente.'],

                        role: ['A função selecionada é inválida.'],
                    },
                },
            },
        })

        findButton('Convidar membro').click()

        await wrapper.vm.$nextTick()

        await fillInvitationForm(wrapper, {
            email: 'duplicado@legalis.local',

            role: 'advogado-junior',
        })

        const submitButton = findButton('Enviar convite')

        expect(submitButton).toBeTruthy()

        submitButton.click()

        await flushPromises()

        expect(document.body.textContent).toContain('Este e-mail já possui convite pendente.')

        expect(document.body.textContent).toContain('A função selecionada é inválida.')

        expect(document.querySelector('.app-dialog')).not.toBeNull()
    })

    it('exibe erro genérico quando envio do convite falha', async () => {
        const { wrapper, invitationsStore } = await mountPage({
            permissions: ['organization-members.invite'],
        })

        vi.spyOn(invitationsStore, 'create').mockRejectedValue(new Error('Falha inesperada'))

        findButton('Convidar membro').click()

        await wrapper.vm.$nextTick()

        await fillInvitationForm(wrapper)

        const submitButton = findButton('Enviar convite')

        expect(submitButton).toBeTruthy()

        submitButton.click()

        await flushPromises()

        expect(document.body.textContent).toContain(
            'Não foi possível enviar o convite. Tente novamente.',
        )

        expect(document.querySelector('.app-dialog')).not.toBeNull()
    })

    it('não mostra Alterar função sem organization-members.update-role', async () => {
        await mountPage({
            permissions: ['organization-members.view'],
        })

        expect(findButton('Alterar função')).toBeUndefined()
    })

    it('mostra Alterar função com organization-members.update-role', async () => {
        await mountPage({
            permissions: ['organization-members.update-role'],
        })

        expect(findButtons('Alterar função')).toHaveLength(2)
    })

    it('abre diálogo para alterar função', async () => {
        const { wrapper } = await mountPage({
            permissions: ['organization-members.update-role'],
        })

        const buttons = findButtons('Alterar função')

        buttons[0].click()

        await wrapper.vm.$nextTick()

        expect(document.body.textContent).toContain('Altere a função de')

        expect(document.body.textContent).toContain('Administrador')

        expect(document.querySelector('#member-role')).not.toBeNull()
    })

    it('altera função do membro', async () => {
        const { wrapper, membersStore } = await mountPage({
            permissions: ['organization-members.update-role'],
        })

        const updateRoleSpy = vi.spyOn(membersStore, 'updateRole').mockResolvedValue()

        findButtons('Alterar função')[0].click()

        await wrapper.vm.$nextTick()

        const role = document.querySelector('#member-role')

        expect(role).not.toBeNull()

        role.value = 'secretaria'

        role.dispatchEvent(
            new Event('change', {
                bubbles: true,
            }),
        )

        await wrapper.vm.$nextTick()

        const saveButton = findButton('Salvar')

        expect(saveButton).toBeTruthy()

        saveButton.click()

        await flushPromises()

        expect(updateRoleSpy).toHaveBeenCalledTimes(1)

        expect(updateRoleSpy).toHaveBeenCalledWith(1, 'secretaria')

        expect(document.querySelector('#member-role')).toBeNull()
    })

    it('exibe erro 422 da função ao alterar membro', async () => {
        const { wrapper, membersStore } = await mountPage({
            permissions: ['organization-members.update-role'],
        })

        vi.spyOn(membersStore, 'updateRole').mockRejectedValue({
            response: {
                status: 422,

                data: {
                    errors: {
                        role: ['A função selecionada é inválida.'],
                    },
                },
            },
        })

        findButtons('Alterar função')[0].click()

        await wrapper.vm.$nextTick()

        const saveButton = findButton('Salvar')

        expect(saveButton).toBeTruthy()

        saveButton.click()

        await flushPromises()

        expect(document.body.textContent).toContain('A função selecionada é inválida.')
    })

    it('exibe erro de domínio ao alterar função', async () => {
        const { wrapper, membersStore } = await mountPage({
            permissions: ['organization-members.update-role'],
        })

        vi.spyOn(membersStore, 'updateRole').mockRejectedValue({
            response: {
                status: 422,

                data: {
                    errors: {
                        member: ['O último administrador ativo não pode ser rebaixado.'],
                    },
                },
            },
        })

        findButtons('Alterar função')[0].click()

        await wrapper.vm.$nextTick()

        const saveButton = findButton('Salvar')

        expect(saveButton).toBeTruthy()

        saveButton.click()

        await flushPromises()

        expect(document.body.textContent).toContain(
            'O último administrador ativo não pode ser rebaixado.',
        )
    })

    it('não mostra ações de status sem organization-members.update-status', async () => {
        await mountPage({
            permissions: ['organization-members.view'],
        })

        expect(findButton('Desativar')).toBeUndefined()

        expect(findButton('Reativar')).toBeUndefined()
    })

    it('mostra ações de status com organization-members.update-status', async () => {
        await mountPage({
            permissions: ['organization-members.update-status'],
        })

        expect(findButton('Desativar')).toBeTruthy()

        expect(findButton('Reativar')).toBeTruthy()
    })

    it('abre confirmação para desativar membro ativo', async () => {
        const { wrapper } = await mountPage({
            permissions: ['organization-members.update-status'],
        })

        findButton('Desativar').click()

        await wrapper.vm.$nextTick()

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()

        expect(document.body.textContent).toContain('Desativar membro')

        expect(document.body.textContent).toContain(
            'Deseja realmente desativar o acesso de "Administrador" ao escritório?',
        )
    })

    it('desativa membro ativo', async () => {
        const { wrapper, membersStore } = await mountPage({
            permissions: ['organization-members.update-status'],
        })

        const updateStatusSpy = vi.spyOn(membersStore, 'updateStatus').mockResolvedValue()

        findButton('Desativar').click()

        await wrapper.vm.$nextTick()

        const dialog = document.querySelector('.app-confirm-dialog')

        expect(dialog).not.toBeNull()

        findButton('Desativar', dialog).click()

        await flushPromises()

        expect(updateStatusSpy).toHaveBeenCalledWith(1, 'inactive')

        expect(document.querySelector('.app-confirm-dialog')).toBeNull()
    })

    it('reativa membro inativo', async () => {
        const { wrapper, membersStore } = await mountPage({
            permissions: ['organization-members.update-status'],
        })

        const updateStatusSpy = vi.spyOn(membersStore, 'updateStatus').mockResolvedValue()

        findButton('Reativar').click()

        await wrapper.vm.$nextTick()

        const dialog = document.querySelector('.app-confirm-dialog')

        expect(dialog).not.toBeNull()

        expect(document.body.textContent).toContain('Reativar membro')

        findButton('Reativar', dialog).click()

        await flushPromises()

        expect(updateStatusSpy).toHaveBeenCalledWith(2, 'active')

        expect(document.querySelector('.app-confirm-dialog')).toBeNull()
    })

    it('cancela alteração de status', async () => {
        const { wrapper, membersStore } = await mountPage({
            permissions: ['organization-members.update-status'],
        })

        const updateStatusSpy = vi.spyOn(membersStore, 'updateStatus')

        findButton('Desativar').click()

        await wrapper.vm.$nextTick()

        const dialog = document.querySelector('.app-confirm-dialog')

        expect(dialog).not.toBeNull()

        findButton('Cancelar', dialog).click()

        await wrapper.vm.$nextTick()

        expect(updateStatusSpy).not.toHaveBeenCalled()

        expect(document.querySelector('.app-confirm-dialog')).toBeNull()
    })

    it('mantém diálogo aberto quando alteração de status falha', async () => {
        const { wrapper, membersStore } = await mountPage({
            permissions: ['organization-members.update-status'],
        })

        vi.spyOn(membersStore, 'updateStatus').mockRejectedValue(
            new Error('Falha ao alterar status'),
        )

        findButton('Desativar').click()

        await wrapper.vm.$nextTick()

        const dialog = document.querySelector('.app-confirm-dialog')

        expect(dialog).not.toBeNull()

        findButton('Desativar', dialog).click()

        await flushPromises()

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()
    })
})
