import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useFolderDeadlinesStore } from '@/stores/folder-deadlines.js'

vi.mock('@/api/folder-deadlines.js', () => ({
    listFolderDeadlines: vi.fn(),
    createFolderDeadline: vi.fn(),
    completeFolderDeadline: vi.fn(),
    deleteFolderDeadline: vi.fn(),
}))

import {
    completeFolderDeadline,
    createFolderDeadline,
    deleteFolderDeadline,
    listFolderDeadlines,
} from '@/api/folder-deadlines.js'

describe('folder deadlines store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useFolderDeadlinesStore()

        expect(store.deadlines).toEqual([])

        expect(store.count).toBe(0)
    })

    it('fetchDeadlines popula coleção', async () => {
        listFolderDeadlines.mockResolvedValue({
            data: [
                {
                    id: 1,
                    folder_id: 10,
                    title: 'Apresentar contestação',
                    due_at: '2026-08-20T12:00:00.000000Z',
                    status: 'pending',
                },

                {
                    id: 2,
                    folder_id: 10,
                    title: 'Protocolar manifestação',
                    due_at: '2026-08-25T12:00:00.000000Z',
                    status: 'pending',
                },
            ],
        })

        const store = useFolderDeadlinesStore()

        const result = await store.fetchDeadlines(10)

        expect(listFolderDeadlines).toHaveBeenCalledTimes(1)

        expect(listFolderDeadlines).toHaveBeenCalledWith(10)

        expect(store.deadlines).toHaveLength(2)

        expect(store.count).toBe(2)

        expect(result).toEqual(store.deadlines)
    })

    it('fetchDeadlines usa array vazio quando resposta não é array', async () => {
        listFolderDeadlines.mockResolvedValue({
            data: null,
        })

        const store = useFolderDeadlinesStore()

        await store.fetchDeadlines(10)

        expect(store.deadlines).toEqual([])

        expect(store.count).toBe(0)
    })

    it('createDeadline adiciona prazo e preserva ordenação por vencimento', async () => {
        const store = useFolderDeadlinesStore()

        store.deadlines = [
            {
                id: 2,
                folder_id: 10,
                title: 'Prazo posterior',
                due_at: '2026-08-30T12:00:00.000000Z',
                status: 'pending',
            },
        ]

        const created = {
            id: 1,
            folder_id: 10,
            title: 'Prazo anterior',
            due_at: '2026-08-20T12:00:00.000000Z',
            status: 'pending',
        }

        createFolderDeadline.mockResolvedValue({
            data: created,
        })

        const payload = {
            title: 'Prazo anterior',

            description: 'Descrição.',

            due_at: '2026-08-20T12:00',
        }

        const result = await store.createDeadline(10, payload)

        expect(createFolderDeadline).toHaveBeenCalledWith(10, payload)

        expect(store.deadlines).toEqual([
            created,

            {
                id: 2,
                folder_id: 10,
                title: 'Prazo posterior',
                due_at: '2026-08-30T12:00:00.000000Z',
                status: 'pending',
            },
        ])

        expect(result).toEqual(created)
    })

    it('completeDeadline atualiza prazo concluído na coleção', async () => {
        const store = useFolderDeadlinesStore()

        store.deadlines = [
            {
                id: 1,
                folder_id: 10,
                title: 'Protocolar petição',
                status: 'pending',
                completed_at: null,
            },
        ]

        const completed = {
            id: 1,
            folder_id: 10,
            title: 'Protocolar petição',
            status: 'completed',
            completed_at: '2026-08-18T14:00:00.000000Z',
        }

        completeFolderDeadline.mockResolvedValue({
            data: completed,
        })

        const result = await store.completeDeadline(10, 1)

        expect(completeFolderDeadline).toHaveBeenCalledWith(10, 1)

        expect(store.deadlines).toEqual([completed])

        expect(result).toEqual(completed)
    })

    it('removeDeadline remove prazo da coleção', async () => {
        deleteFolderDeadline.mockResolvedValue({
            data: null,
        })

        const store = useFolderDeadlinesStore()

        store.deadlines = [
            {
                id: 1,
                folder_id: 10,
                title: 'Prazo A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Prazo B',
            },
        ]

        await store.removeDeadline(10, 1)

        expect(deleteFolderDeadline).toHaveBeenCalledWith(10, 1)

        expect(store.deadlines).toEqual([
            {
                id: 2,
                folder_id: 10,
                title: 'Prazo B',
            },
        ])

        expect(store.count).toBe(1)
    })

    it('removeDeadline aceita id string', async () => {
        deleteFolderDeadline.mockResolvedValue({
            data: null,
        })

        const store = useFolderDeadlinesStore()

        store.deadlines = [
            {
                id: 1,
                folder_id: 10,
                title: 'Prazo A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Prazo B',
            },
        ]

        await store.removeDeadline(10, '1')

        expect(store.deadlines).toEqual([
            {
                id: 2,
                folder_id: 10,
                title: 'Prazo B',
            },
        ])
    })

    it('clear limpa prazos', () => {
        const store = useFolderDeadlinesStore()

        store.deadlines = [
            {
                id: 1,
                folder_id: 10,
                title: 'Prazo A',
            },
        ]

        store.clear()

        expect(store.deadlines).toEqual([])

        expect(store.count).toBe(0)
    })
})
