import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { createOrganizationInvitation } from '@/api/organization-invitations.js'

import { useOrganizationInvitationsStore } from '@/stores/organization-invitations.js'

vi.mock('@/api/organization-invitations.js', () => ({
    createOrganizationInvitation: vi.fn(),
}))

describe('organization invitations store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia sem convite e sem carregamento', () => {
        const store = useOrganizationInvitationsStore()

        expect(store.invitation).toBeNull()

        expect(store.creating).toBe(false)
    })

    it('cria convite', async () => {
        const response = {
            id: 10,
            email: 'novo@legalis.local',
            role: 'advogado',
            status: 'pending',
        }

        createOrganizationInvitation.mockResolvedValue(response)

        const store = useOrganizationInvitationsStore()

        const result = await store.create({
            email: 'novo@legalis.local',
            role: 'advogado',
        })

        expect(createOrganizationInvitation).toHaveBeenCalledTimes(1)

        expect(createOrganizationInvitation).toHaveBeenCalledWith({
            email: 'novo@legalis.local',
            role: 'advogado',
        })

        expect(store.invitation).toEqual(response)

        expect(result).toEqual(response)

        expect(store.creating).toBe(false)
    })

    it('indica carregamento durante a criação', async () => {
        let resolveRequest

        createOrganizationInvitation.mockImplementation(
            () =>
                new Promise((resolve) => {
                    resolveRequest = resolve
                }),
        )

        const store = useOrganizationInvitationsStore()

        const promise = store.create({
            email: 'novo@legalis.local',
            role: 'advogado',
        })

        expect(store.creating).toBe(true)

        resolveRequest({
            id: 10,
        })

        await promise

        expect(store.creating).toBe(false)
    })

    it('encerra carregamento quando a criação falha', async () => {
        createOrganizationInvitation.mockRejectedValue(new Error('Falha ao criar convite'))

        const store = useOrganizationInvitationsStore()

        await expect(
            store.create({
                email: 'novo@legalis.local',
                role: 'advogado',
            }),
        ).rejects.toThrow('Falha ao criar convite')

        expect(store.invitation).toBeNull()

        expect(store.creating).toBe(false)
    })

    it('limpa estado', async () => {
        createOrganizationInvitation.mockResolvedValue({
            id: 10,
            email: 'novo@legalis.local',
        })

        const store = useOrganizationInvitationsStore()

        await store.create({
            email: 'novo@legalis.local',
            role: 'advogado',
        })

        expect(store.invitation).not.toBeNull()

        store.clear()

        expect(store.invitation).toBeNull()

        expect(store.creating).toBe(false)
    })
})
