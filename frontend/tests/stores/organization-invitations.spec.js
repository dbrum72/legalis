import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import {
    createOrganizationInvitation,
    listOrganizationInvitations,
    resendOrganizationInvitation,
    revokeOrganizationInvitation,
} from '@/api/organization-invitations.js'

import { useOrganizationInvitationsStore } from '@/stores/organization-invitations.js'

vi.mock('@/api/organization-invitations.js', () => ({
    createOrganizationInvitation: vi.fn(),
    listOrganizationInvitations: vi.fn(),
    resendOrganizationInvitation: vi.fn(),
    revokeOrganizationInvitation: vi.fn(),
}))

function invitation(overrides = {}) {
    return {
        id: 10,
        email: 'novo@legalis.local',
        role: 'advogado-junior',
        status: 'pending',
        expires_at: '2026-09-04T12:00:00.000000Z',
        accepted_at: null,
        revoked_at: null,
        created_at: '2026-08-28T12:00:00.000000Z',
        inviter: {
            id: 1,
            name: 'Super Admin',
            email: 'super-admin@legalis.local',
        },
        ...overrides,
    }
}

describe('organization invitations store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado administrativo vazio', () => {
        const store = useOrganizationInvitationsStore()

        expect(store.invitations).toEqual([])
        expect(store.invitation).toBeNull()

        expect(store.fetching).toBe(false)
        expect(store.creating).toBe(false)

        expect(store.resendingId).toBeNull()
        expect(store.revokingId).toBeNull()
    })

    it('lista convites administrativos', async () => {
        const response = [
            invitation(),
            invitation({
                id: 9,
                email: 'expirado@legalis.local',
                status: 'expired',
            }),
        ]

        listOrganizationInvitations.mockResolvedValue(response)

        const store = useOrganizationInvitationsStore()

        const result = await store.fetchInvitations()

        expect(listOrganizationInvitations).toHaveBeenCalledOnce()

        expect(store.invitations).toEqual(response)
        expect(result).toEqual(response)

        expect(store.fetching).toBe(false)
    })

    it('indica carregamento durante a listagem', async () => {
        let resolveRequest

        listOrganizationInvitations.mockImplementation(
            () =>
                new Promise((resolve) => {
                    resolveRequest = resolve
                }),
        )

        const store = useOrganizationInvitationsStore()

        const promise = store.fetchInvitations()

        expect(store.fetching).toBe(true)

        resolveRequest([])

        await promise

        expect(store.fetching).toBe(false)
    })

    it('encerra carregamento quando a listagem falha', async () => {
        listOrganizationInvitations.mockRejectedValue(new Error('Falha ao listar convites'))

        const store = useOrganizationInvitationsStore()

        await expect(store.fetchInvitations()).rejects.toThrow('Falha ao listar convites')

        expect(store.invitations).toEqual([])
        expect(store.fetching).toBe(false)
    })

    it('cria convite e o adiciona ao início da listagem', async () => {
        const existingInvitation = invitation({
            id: 9,
            email: 'existente@legalis.local',
        })

        const createdInvitation = invitation()

        listOrganizationInvitations.mockResolvedValue([existingInvitation])

        createOrganizationInvitation.mockResolvedValue(createdInvitation)

        const store = useOrganizationInvitationsStore()

        await store.fetchInvitations()

        const result = await store.create({
            email: 'novo@legalis.local',
            role: 'advogado-junior',
        })

        expect(createOrganizationInvitation).toHaveBeenCalledOnce()

        expect(createOrganizationInvitation).toHaveBeenCalledWith({
            email: 'novo@legalis.local',
            role: 'advogado-junior',
        })

        expect(store.invitation).toEqual(createdInvitation)

        expect(store.invitations).toEqual([createdInvitation, existingInvitation])

        expect(result).toEqual(createdInvitation)

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
            role: 'advogado-junior',
        })

        expect(store.creating).toBe(true)

        resolveRequest(invitation())

        await promise

        expect(store.creating).toBe(false)
    })

    it('encerra carregamento quando a criação falha', async () => {
        createOrganizationInvitation.mockRejectedValue(new Error('Falha ao criar convite'))

        const store = useOrganizationInvitationsStore()

        await expect(
            store.create({
                email: 'novo@legalis.local',
                role: 'advogado-junior',
            }),
        ).rejects.toThrow('Falha ao criar convite')

        expect(store.invitation).toBeNull()
        expect(store.invitations).toEqual([])
        expect(store.creating).toBe(false)
    })

    it('reenvia convite e atualiza o item correspondente', async () => {
        const currentInvitation = invitation()

        const resentInvitation = invitation({
            expires_at: '2026-09-11T12:00:00.000000Z',
        })

        listOrganizationInvitations.mockResolvedValue([currentInvitation])

        resendOrganizationInvitation.mockResolvedValue(resentInvitation)

        const store = useOrganizationInvitationsStore()

        await store.fetchInvitations()

        const result = await store.resend(currentInvitation.id)

        expect(resendOrganizationInvitation).toHaveBeenCalledWith(currentInvitation.id)

        expect(store.invitations).toEqual([resentInvitation])

        expect(result).toEqual(resentInvitation)

        expect(store.resendingId).toBeNull()
    })

    it('identifica o convite durante o reenvio', async () => {
        let resolveRequest

        resendOrganizationInvitation.mockImplementation(
            () =>
                new Promise((resolve) => {
                    resolveRequest = resolve
                }),
        )

        const store = useOrganizationInvitationsStore()

        const promise = store.resend(10)

        expect(store.resendingId).toBe(10)

        resolveRequest(invitation())

        await promise

        expect(store.resendingId).toBeNull()
    })

    it('encerra estado quando o reenvio falha', async () => {
        resendOrganizationInvitation.mockRejectedValue(new Error('Falha ao reenviar convite'))

        const store = useOrganizationInvitationsStore()

        await expect(store.resend(10)).rejects.toThrow('Falha ao reenviar convite')

        expect(store.resendingId).toBeNull()
    })

    it('revoga convite e atualiza o item correspondente', async () => {
        const currentInvitation = invitation()

        const revokedInvitation = invitation({
            status: 'revoked',
            revoked_at: '2026-08-28T13:00:00.000000Z',
        })

        listOrganizationInvitations.mockResolvedValue([currentInvitation])

        revokeOrganizationInvitation.mockResolvedValue(revokedInvitation)

        const store = useOrganizationInvitationsStore()

        await store.fetchInvitations()

        const result = await store.revoke(currentInvitation.id)

        expect(revokeOrganizationInvitation).toHaveBeenCalledWith(currentInvitation.id)

        expect(store.invitations).toEqual([revokedInvitation])

        expect(result).toEqual(revokedInvitation)

        expect(store.revokingId).toBeNull()
    })

    it('identifica o convite durante a revogação', async () => {
        let resolveRequest

        revokeOrganizationInvitation.mockImplementation(
            () =>
                new Promise((resolve) => {
                    resolveRequest = resolve
                }),
        )

        const store = useOrganizationInvitationsStore()

        const promise = store.revoke(10)

        expect(store.revokingId).toBe(10)

        resolveRequest(
            invitation({
                status: 'revoked',
            }),
        )

        await promise

        expect(store.revokingId).toBeNull()
    })

    it('encerra estado quando a revogação falha', async () => {
        revokeOrganizationInvitation.mockRejectedValue(new Error('Falha ao revogar convite'))

        const store = useOrganizationInvitationsStore()

        await expect(store.revoke(10)).rejects.toThrow('Falha ao revogar convite')

        expect(store.revokingId).toBeNull()
    })

    it('limpa todo o estado administrativo', async () => {
        listOrganizationInvitations.mockResolvedValue([invitation()])

        createOrganizationInvitation.mockResolvedValue(
            invitation({
                id: 11,
                email: 'segundo@legalis.local',
            }),
        )

        const store = useOrganizationInvitationsStore()

        await store.fetchInvitations()

        await store.create({
            email: 'segundo@legalis.local',
            role: 'advogado-junior',
        })

        store.clear()

        expect(store.invitations).toEqual([])
        expect(store.invitation).toBeNull()

        expect(store.fetching).toBe(false)
        expect(store.creating).toBe(false)

        expect(store.resendingId).toBeNull()
        expect(store.revokingId).toBeNull()
    })
})
