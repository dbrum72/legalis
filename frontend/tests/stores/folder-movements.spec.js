import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useFolderMovementsStore } from '@/stores/folder-movements.js'

vi.mock('@/api/folder-movements.js', () => ({
    listFolderMovements: vi.fn(),
    createFolderMovement: vi.fn(),
    deleteFolderMovement: vi.fn(),
}))

import {
    createFolderMovement,
    deleteFolderMovement,
    listFolderMovements,
} from '@/api/folder-movements.js'

describe('folder movements store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useFolderMovementsStore()

        expect(store.movements).toEqual([])

        expect(store.count).toBe(0)
    })

    it('fetchMovements popula coleção', async () => {
        listFolderMovements.mockResolvedValue({
            data: [
                {
                    id: 1,
                    folder_id: 10,
                    title: 'Despacho publicado',
                    occurred_at: '2026-08-17T16:00:00.000000Z',
                },

                {
                    id: 2,
                    folder_id: 10,
                    title: 'Petição protocolada',
                    occurred_at: '2026-08-16T14:00:00.000000Z',
                },
            ],
        })

        const store = useFolderMovementsStore()

        const result = await store.fetchMovements(10)

        expect(listFolderMovements).toHaveBeenCalledTimes(1)

        expect(listFolderMovements).toHaveBeenCalledWith(10)

        expect(store.movements).toHaveLength(2)

        expect(store.count).toBe(2)

        expect(result).toEqual(store.movements)
    })

    it('fetchMovements usa array vazio quando resposta não é array', async () => {
        listFolderMovements.mockResolvedValue({
            data: null,
        })

        const store = useFolderMovementsStore()

        await store.fetchMovements(10)

        expect(store.movements).toEqual([])

        expect(store.count).toBe(0)
    })

    it('createMovement adiciona movimentação no início da coleção', async () => {
        const store = useFolderMovementsStore()

        store.movements = [
            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação anterior',
            },
        ]

        const created = {
            id: 2,
            folder_id: 10,
            title: 'Nova movimentação',
            occurred_at: '2026-08-17T18:00:00.000000Z',
        }

        createFolderMovement.mockResolvedValue({
            data: created,
        })

        const payload = {
            occurred_at: '2026-08-17 18:00:00',

            title: 'Nova movimentação',

            description: 'Descrição.',
        }

        const result = await store.createMovement(10, payload)

        expect(createFolderMovement).toHaveBeenCalledTimes(1)

        expect(createFolderMovement).toHaveBeenCalledWith(10, payload)

        expect(store.movements).toEqual([
            created,

            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação anterior',
            },
        ])

        expect(result).toEqual(created)
    })

    it('removeMovement remove movimentação da coleção', async () => {
        deleteFolderMovement.mockResolvedValue({
            data: null,
        })

        const store = useFolderMovementsStore()

        store.movements = [
            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Movimentação B',
            },
        ]

        await store.removeMovement(10, 1)

        expect(deleteFolderMovement).toHaveBeenCalledTimes(1)

        expect(deleteFolderMovement).toHaveBeenCalledWith(10, 1)

        expect(store.movements).toEqual([
            {
                id: 2,
                folder_id: 10,
                title: 'Movimentação B',
            },
        ])

        expect(store.count).toBe(1)
    })

    it('removeMovement aceita id string', async () => {
        deleteFolderMovement.mockResolvedValue({
            data: null,
        })

        const store = useFolderMovementsStore()

        store.movements = [
            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Movimentação B',
            },
        ]

        await store.removeMovement(10, '1')

        expect(store.movements).toEqual([
            {
                id: 2,
                folder_id: 10,
                title: 'Movimentação B',
            },
        ])
    })

    it('removeMovement preserva coleção quando id não existe', async () => {
        deleteFolderMovement.mockResolvedValue({
            data: null,
        })

        const store = useFolderMovementsStore()

        store.movements = [
            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação A',
            },
        ]

        await store.removeMovement(10, 999)

        expect(store.movements).toEqual([
            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação A',
            },
        ])
    })

    it('clear limpa movimentações', () => {
        const store = useFolderMovementsStore()

        store.movements = [
            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Movimentação B',
            },
        ]

        store.clear()

        expect(store.movements).toEqual([])

        expect(store.count).toBe(0)
    })
})
