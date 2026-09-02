import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/organization-roles.js', () => ({
    listOrganizationRoles: vi.fn(),
    getOrganizationRole: vi.fn(),
    updateOrganizationRolePermissions: vi.fn(),
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

import {
    getOrganizationRole,
    listOrganizationRoles,
    updateOrganizationRolePermissions,
} from '@/api/organization-roles.js'
import RolePermissionsPage from '@/views/settings/RolePermissionsPage.vue'
import { useAuthStore } from '@/stores/auth.js'

const roles = [
    { id: 1, name: 'advogado-pleno', description: 'Atuação plena', permissions_count: 1 },
    { id: 2, name: 'super-admin', description: 'Acesso total', permissions_count: 2 },
]
const roleDetails = {
    1: {
        ...roles[0],
        permissions: ['clients.view'],
        available_permissions: ['clients.view', 'clients.create', 'folders.view'],
    },
    2: {
        ...roles[1],
        permissions: ['clients.view', 'clients.create'],
        available_permissions: ['clients.view', 'clients.create'],
    },
}

async function mountPage() {
    const pinia = createPinia()
    setActivePinia(pinia)
    useAuthStore().permissions = ['roles.view', 'roles.update']
    listOrganizationRoles.mockResolvedValue({ data: roles })
    getOrganizationRole.mockImplementation((id) => Promise.resolve({ data: roleDetails[id] }))
    const wrapper = mount(RolePermissionsPage, { global: { plugins: [pinia] } })
    await flushPromises()
    return wrapper
}

describe('RolePermissionsPage', () => {
    beforeEach(() => vi.clearAllMocks())

    it('carrega funções e exibe a matriz da primeira função', async () => {
        const wrapper = await mountPage()
        expect(wrapper.text()).toContain('Advogado Pleno')
        expect(wrapper.text()).toContain('clients.view')
        expect(wrapper.text()).toContain('1 de 3')
    })

    it('seleciona outra função pela faixa horizontal', async () => {
        const wrapper = await mountPage()
        await wrapper.findAll('.role-permissions__role')[1].trigger('click')
        await flushPromises()
        expect(getOrganizationRole).toHaveBeenLastCalledWith(2)
        expect(wrapper.text()).toContain('Acesso total')
    })

    it('altera permissão individual e permite descartar', async () => {
        const wrapper = await mountPage()
        await wrapper.get('#permission-clients-create').setValue(true)
        expect(wrapper.text()).toContain('1 alteração pendente')
        const discard = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Descartar'))
        await discard.trigger('click')
        expect(wrapper.text()).toContain('Nenhuma alteração pendente')
    })

    it('altera um grupo e salva as permissões', async () => {
        const wrapper = await mountPage()
        updateOrganizationRolePermissions.mockResolvedValue({
            data: { ...roleDetails[1], permissions: ['clients.create', 'clients.view'] },
        })
        await wrapper.get('#permission-group-clients').setValue(true)
        const save = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Salvar alterações'))
        await save.trigger('click')
        await flushPromises()
        expect(updateOrganizationRolePermissions).toHaveBeenCalledWith(1, [
            'clients.create',
            'clients.view',
        ])
    })

    it('mantém os checkboxes do Super Admin protegidos', async () => {
        const wrapper = await mountPage()
        await wrapper.findAll('.role-permissions__role')[1].trigger('click')
        await flushPromises()
        expect(wrapper.get('#permission-clients-view').attributes('disabled')).toBeDefined()
    })
})
