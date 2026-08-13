import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useMaritalStatusesStore } from '@/stores/marital-statuses.js'

vi.mock('@/api/marital-statuses.js', () => ({
    listMaritalStatuses: vi.fn(),
}))

import { listMaritalStatuses } from '@/api/marital-statuses.js'

describe('marital statuses store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useMaritalStatusesStore()

        expect(store.maritalStatuses).toEqual([])

        expect(store.options).toEqual([])
    })

    it('fetchMaritalStatuses popula catálogo', async () => {
        listMaritalStatuses.mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'solteiro(a)',
                },
                {
                    id: 2,
                    name: 'casado(a)',
                },
            ],
        })

        const store = useMaritalStatusesStore()

        const result = await store.fetchMaritalStatuses()

        expect(listMaritalStatuses).toHaveBeenCalledTimes(1)

        expect(store.maritalStatuses).toEqual([
            {
                id: 1,
                name: 'solteiro(a)',
            },
            {
                id: 2,
                name: 'casado(a)',
            },
        ])

        expect(result).toEqual([
            {
                id: 1,
                name: 'solteiro(a)',
            },
            {
                id: 2,
                name: 'casado(a)',
            },
        ])
    })

    it('fetchMaritalStatuses usa array vazio quando resposta não é array', async () => {
        listMaritalStatuses.mockResolvedValue({
            data: null,
        })

        const store = useMaritalStatusesStore()

        const result = await store.fetchMaritalStatuses()

        expect(store.maritalStatuses).toEqual([])

        expect(result).toEqual([])
    })

    it('options converte catálogo para label e value', () => {
        const store = useMaritalStatusesStore()

        store.maritalStatuses = [
            {
                id: 1,
                name: 'solteiro(a)',
            },
            {
                id: 2,
                name: 'casado(a)',
            },
            {
                id: 3,
                name: 'divorciado(a)',
            },
        ]

        expect(store.options).toEqual([
            {
                label: 'solteiro(a)',
                value: 1,
            },
            {
                label: 'casado(a)',
                value: 2,
            },
            {
                label: 'divorciado(a)',
                value: 3,
            },
        ])
    })

    it('options reage a alterações do catálogo', () => {
        const store = useMaritalStatusesStore()

        store.maritalStatuses = [
            {
                id: 1,
                name: 'solteiro(a)',
            },
        ]

        expect(store.options).toHaveLength(1)

        store.maritalStatuses.push({
            id: 2,
            name: 'casado(a)',
        })

        expect(store.options).toEqual([
            {
                label: 'solteiro(a)',
                value: 1,
            },
            {
                label: 'casado(a)',
                value: 2,
            },
        ])
    })

    it('clear esvazia catálogo e options', () => {
        const store = useMaritalStatusesStore()

        store.maritalStatuses = [
            {
                id: 1,
                name: 'solteiro(a)',
            },
        ]

        store.clear()

        expect(store.maritalStatuses).toEqual([])

        expect(store.options).toEqual([])
    })
})
