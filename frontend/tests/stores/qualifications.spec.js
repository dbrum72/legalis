import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useQualificationsStore } from '@/stores/qualifications.js'

vi.mock('@/api/qualifications.js', () => ({
    listQualifications: vi.fn(),
}))

import { listQualifications } from '@/api/qualifications.js'

describe('qualifications store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useQualificationsStore()

        expect(store.qualifications).toEqual([])

        expect(store.options).toEqual([])
    })

    it('fetchQualifications popula catálogo', async () => {
        listQualifications.mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Autor',
                },
                {
                    id: 2,
                    name: 'Réu',
                },
            ],
        })

        const store = useQualificationsStore()

        const result = await store.fetchQualifications()

        expect(listQualifications).toHaveBeenCalledTimes(1)

        expect(store.qualifications).toEqual([
            {
                id: 1,
                name: 'Autor',
            },
            {
                id: 2,
                name: 'Réu',
            },
        ])

        expect(result).toEqual(store.qualifications)
    })

    it('usa array vazio quando resposta não é array', async () => {
        listQualifications.mockResolvedValue({
            data: null,
        })

        const store = useQualificationsStore()

        await store.fetchQualifications()

        expect(store.qualifications).toEqual([])

        expect(store.options).toEqual([])
    })

    it('options converte catálogo para label e value', () => {
        const store = useQualificationsStore()

        store.qualifications = [
            {
                id: 10,
                name: 'Autor',
            },
            {
                id: 20,
                name: 'Réu',
            },
        ]

        expect(store.options).toEqual([
            {
                label: 'Autor',
                value: 10,
            },
            {
                label: 'Réu',
                value: 20,
            },
        ])
    })

    it('getById localiza qualificação', () => {
        const store = useQualificationsStore()

        store.qualifications = [
            {
                id: 10,
                name: 'Autor',
            },
        ]

        expect(store.getById('10')).toEqual({
            id: 10,
            name: 'Autor',
        })
    })

    it('getById retorna null quando não encontra', () => {
        const store = useQualificationsStore()

        expect(store.getById(999)).toBeNull()
    })

    it('clear limpa catálogo e options', () => {
        const store = useQualificationsStore()

        store.qualifications = [
            {
                id: 1,
                name: 'Autor',
            },
        ]

        store.clear()

        expect(store.qualifications).toEqual([])

        expect(store.options).toEqual([])
    })
})
