import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useOrganizationRolesStore } from '@/stores/organization-roles.js'

vi.mock('@/api/organization-roles.js', () => ({
    listOrganizationRoles: vi.fn(),
}))

import { listOrganizationRoles } from '@/api/organization-roles.js'

describe('organization roles store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useOrganizationRolesStore()

        expect(store.roles).toEqual([])

        expect(store.count).toBe(0)

        expect(store.options).toEqual([])
    })

    it('fetchRoles popula o catálogo', async () => {
        const roles = [
            {
                id: 1,
                name: 'advogado-junior',
            },
            {
                id: 2,
                name: 'advogado-pleno',
            },
        ]

        listOrganizationRoles.mockResolvedValue({
            data: roles,
        })

        const store = useOrganizationRolesStore()

        const result = await store.fetchRoles()

        expect(listOrganizationRoles).toHaveBeenCalledTimes(1)

        expect(store.roles).toEqual(roles)

        expect(store.count).toBe(2)

        expect(result).toEqual(roles)
    })

    it('fetchRoles usa array vazio quando resposta não é array', async () => {
        listOrganizationRoles.mockResolvedValue({
            data: null,
        })

        const store = useOrganizationRolesStore()

        const result = await store.fetchRoles()

        expect(store.roles).toEqual([])

        expect(result).toEqual([])
    })

    it('gera opções para AppSelect', () => {
        const store = useOrganizationRolesStore()

        store.roles = [
            {
                id: 1,
                name: 'advogado-junior',
            },
            {
                id: 2,
                name: 'socio-administrador',
            },
        ]

        expect(store.options).toEqual([
            {
                label: 'advogado-junior',
                value: 'advogado-junior',
            },
            {
                label: 'socio-administrador',
                value: 'socio-administrador',
            },
        ])
    })

    it('getById localiza role por id numérico ou string', () => {
        const store = useOrganizationRolesStore()

        store.roles = [
            {
                id: 10,
                name: 'advogado-junior',
            },
        ]

        expect(store.getById(10)).toEqual(store.roles[0])

        expect(store.getById('10')).toEqual(store.roles[0])
    })

    it('getByName localiza role pelo nome', () => {
        const store = useOrganizationRolesStore()

        store.roles = [
            {
                id: 10,
                name: 'advogado-junior',
            },
        ]

        expect(store.getByName('advogado-junior')).toEqual(store.roles[0])

        expect(store.getByName('inexistente')).toBeNull()
    })

    it('clear limpa o catálogo', () => {
        const store = useOrganizationRolesStore()

        store.roles = [
            {
                id: 10,
                name: 'advogado-junior',
            },
        ]

        store.clear()

        expect(store.roles).toEqual([])

        expect(store.count).toBe(0)
    })
})
