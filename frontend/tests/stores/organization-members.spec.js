import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useOrganizationMembersStore } from '@/stores/organization-members.js'

vi.mock('@/api/organization-members.js', () => ({
    listOrganizationMembers: vi.fn(),

    updateOrganizationMemberRole: vi.fn(),

    updateOrganizationMemberStatus: vi.fn(),
}))

import {
    listOrganizationMembers,
    updateOrganizationMemberRole,
    updateOrganizationMemberStatus,
} from '@/api/organization-members.js'

describe('organization members store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useOrganizationMembersStore()

        expect(store.members).toEqual([])

        expect(store.count).toBe(0)

        expect(store.activeMembers).toEqual([])

        expect(store.inactiveMembers).toEqual([])
    })

    it('getById retorna membro existente', () => {
        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                name: 'Usuário A',
            },
            {
                id: 2,
                name: 'Usuário B',
            },
        ]

        expect(store.getById(2)).toEqual({
            id: 2,
            name: 'Usuário B',
        })
    })

    it('getById aceita id em formato string', () => {
        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                name: 'Usuário A',
            },
        ]

        expect(store.getById('1')).toEqual({
            id: 1,
            name: 'Usuário A',
        })
    })

    it('getById retorna null quando membro não existe', () => {
        const store = useOrganizationMembersStore()

        expect(store.getById(999)).toBeNull()
    })

    it('separa membros ativos e inativos pelo status retornado pela api', () => {
        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                status: 'active',
            },
            {
                id: 2,
                status: 'inactive',
            },
            {
                id: 3,
                status: 'active',
            },
        ]

        expect(store.activeMembers.map((member) => member.id)).toEqual([1, 3])

        expect(store.inactiveMembers.map((member) => member.id)).toEqual([2])
    })

    it('fetchMembers popula a lista', async () => {
        const members = [
            {
                id: 1,
                name: 'Usuário A',
                status: 'active',
                role: 'advogado-junior',
            },
            {
                id: 2,
                name: 'Usuário B',
                status: 'inactive',
                role: 'advogado-pleno',
            },
        ]

        listOrganizationMembers.mockResolvedValue({
            data: members,
        })

        const store = useOrganizationMembersStore()

        const result = await store.fetchMembers()

        expect(listOrganizationMembers).toHaveBeenCalledTimes(1)

        expect(store.members).toEqual(members)

        expect(store.count).toBe(2)

        expect(result).toEqual(members)
    })

    it('fetchMembers usa array vazio quando a resposta não é array', async () => {
        listOrganizationMembers.mockResolvedValue({
            data: null,
        })

        const store = useOrganizationMembersStore()

        const result = await store.fetchMembers()

        expect(store.members).toEqual([])

        expect(result).toEqual([])
    })

    it('updateRole envia a nova role e atualiza o membro local', async () => {
        updateOrganizationMemberRole.mockResolvedValue({
            data: {
                message: 'Função do membro atualizada com sucesso.',
            },
        })

        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                name: 'Usuário A',
                status: 'active',
                role: 'advogado-junior',
            },
        ]

        const result = await store.updateRole(1, 'advogado-pleno')

        expect(updateOrganizationMemberRole).toHaveBeenCalledWith(1, 'advogado-pleno')

        expect(store.members[0].role).toBe('advogado-pleno')

        expect(result).toEqual(store.members[0])
    })

    it('updateRole não cria membro ausente', async () => {
        updateOrganizationMemberRole.mockResolvedValue({
            data: {
                message: 'Função do membro atualizada com sucesso.',
            },
        })

        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                role: 'advogado-junior',
            },
        ]

        const result = await store.updateRole(99, 'advogado-pleno')

        expect(result).toBeNull()

        expect(store.members).toEqual([
            {
                id: 1,
                role: 'advogado-junior',
            },
        ])
    })

    it('updateStatus envia o novo status e atualiza o membro local', async () => {
        updateOrganizationMemberStatus.mockResolvedValue({
            data: {
                message: 'Status do membro atualizado com sucesso.',
            },
        })

        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                name: 'Usuário A',
                status: 'active',
                role: 'advogado-junior',
            },
        ]

        const result = await store.updateStatus(1, 'inactive')

        expect(updateOrganizationMemberStatus).toHaveBeenCalledWith(1, 'inactive')

        expect(store.members[0].status).toBe('inactive')

        expect(store.activeMembers).toEqual([])

        expect(store.inactiveMembers).toEqual([store.members[0]])

        expect(result).toEqual(store.members[0])
    })

    it('updateStatus não cria membro ausente', async () => {
        updateOrganizationMemberStatus.mockResolvedValue({
            data: {
                message: 'Status do membro atualizado com sucesso.',
            },
        })

        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                status: 'active',
            },
        ]

        const result = await store.updateStatus(99, 'inactive')

        expect(result).toBeNull()

        expect(store.members).toEqual([
            {
                id: 1,
                status: 'active',
            },
        ])
    })

    it('não altera role local quando a api falha', async () => {
        updateOrganizationMemberRole.mockRejectedValue(new Error('Falha ao atualizar role'))

        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                role: 'advogado-junior',
            },
        ]

        await expect(store.updateRole(1, 'advogado-pleno')).rejects.toThrow(
            'Falha ao atualizar role',
        )

        expect(store.members[0].role).toBe('advogado-junior')
    })

    it('não altera status local quando a api falha', async () => {
        updateOrganizationMemberStatus.mockRejectedValue(new Error('Falha ao atualizar status'))

        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
                status: 'active',
            },
        ]

        await expect(store.updateStatus(1, 'inactive')).rejects.toThrow('Falha ao atualizar status')

        expect(store.members[0].status).toBe('active')
    })

    it('clear limpa a lista', () => {
        const store = useOrganizationMembersStore()

        store.members = [
            {
                id: 1,
            },
        ]

        store.clear()

        expect(store.members).toEqual([])

        expect(store.count).toBe(0)
    })
})
