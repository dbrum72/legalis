import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useFolderEventsStore } from '@/stores/folder-events.js'

vi.mock('@/api/folder-events.js', () => ({
    listFolderEvents: vi.fn(),
    createFolderEvent: vi.fn(),
    completeFolderEvent: vi.fn(),
    deleteFolderEvent: vi.fn(),
}))

import {
    completeFolderEvent,
    createFolderEvent,
    deleteFolderEvent,
    listFolderEvents,
} from '@/api/folder-events.js'

describe('folder events store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useFolderEventsStore()

        expect(store.events).toEqual([])

        expect(store.count).toBe(0)
    })

    it('fetchEvents popula coleção', async () => {
        listFolderEvents.mockResolvedValue({
            data: [
                {
                    id: 1,
                    folder_id: 10,
                    type: 'hearing',
                    title: 'Audiência',
                    starts_at: '2026-09-10T14:00:00.000000Z',
                    status: 'scheduled',
                },

                {
                    id: 2,
                    folder_id: 10,
                    type: 'meeting',
                    title: 'Reunião',
                    starts_at: '2026-09-12T10:00:00.000000Z',
                    status: 'scheduled',
                },
            ],
        })

        const store = useFolderEventsStore()

        const result = await store.fetchEvents(10)

        expect(listFolderEvents).toHaveBeenCalledTimes(1)

        expect(listFolderEvents).toHaveBeenCalledWith(10)

        expect(store.events).toHaveLength(2)

        expect(store.count).toBe(2)

        expect(result).toEqual(store.events)
    })

    it('fetchEvents usa array vazio quando resposta não é array', async () => {
        listFolderEvents.mockResolvedValue({
            data: null,
        })

        const store = useFolderEventsStore()

        await store.fetchEvents(10)

        expect(store.events).toEqual([])

        expect(store.count).toBe(0)
    })

    it('createEvent adiciona evento e preserva ordenação cronológica', async () => {
        const store = useFolderEventsStore()

        store.events = [
            {
                id: 2,
                folder_id: 10,
                type: 'meeting',
                title: 'Evento posterior',
                starts_at: '2026-09-20T14:00:00.000000Z',
                status: 'scheduled',
            },
        ]

        const created = {
            id: 1,
            folder_id: 10,
            type: 'hearing',
            title: 'Evento anterior',
            starts_at: '2026-09-10T14:00:00.000000Z',
            status: 'scheduled',
        }

        createFolderEvent.mockResolvedValue({
            data: created,
        })

        const payload = {
            type: 'hearing',

            title: 'Evento anterior',

            description: 'Audiência de instrução.',

            starts_at: '2026-09-10T14:00',

            ends_at: '2026-09-10T15:30',

            location: '3ª Vara Cível',
        }

        const result = await store.createEvent(10, payload)

        expect(createFolderEvent).toHaveBeenCalledWith(10, payload)

        expect(store.events).toEqual([
            created,

            {
                id: 2,
                folder_id: 10,
                type: 'meeting',
                title: 'Evento posterior',
                starts_at: '2026-09-20T14:00:00.000000Z',
                status: 'scheduled',
            },
        ])

        expect(result).toEqual(created)
    })

    it('completeEvent atualiza evento concluído na coleção', async () => {
        const store = useFolderEventsStore()

        store.events = [
            {
                id: 1,
                folder_id: 10,
                type: 'hearing',
                title: 'Audiência',
                status: 'scheduled',
                completed_at: null,
            },
        ]

        const completed = {
            id: 1,
            folder_id: 10,
            type: 'hearing',
            title: 'Audiência',
            status: 'completed',
            completed_at: '2026-09-10T16:00:00.000000Z',
        }

        completeFolderEvent.mockResolvedValue({
            data: completed,
        })

        const result = await store.completeEvent(10, 1)

        expect(completeFolderEvent).toHaveBeenCalledWith(10, 1)

        expect(store.events).toEqual([completed])

        expect(result).toEqual(completed)
    })

    it('removeEvent remove evento da coleção', async () => {
        deleteFolderEvent.mockResolvedValue({
            data: null,
        })

        const store = useFolderEventsStore()

        store.events = [
            {
                id: 1,
                folder_id: 10,
                title: 'Evento A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Evento B',
            },
        ]

        await store.removeEvent(10, 1)

        expect(deleteFolderEvent).toHaveBeenCalledWith(10, 1)

        expect(store.events).toEqual([
            {
                id: 2,
                folder_id: 10,
                title: 'Evento B',
            },
        ])

        expect(store.count).toBe(1)
    })

    it('removeEvent aceita id string', async () => {
        deleteFolderEvent.mockResolvedValue({
            data: null,
        })

        const store = useFolderEventsStore()

        store.events = [
            {
                id: 1,
                folder_id: 10,
                title: 'Evento A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Evento B',
            },
        ]

        await store.removeEvent(10, '1')

        expect(store.events).toEqual([
            {
                id: 2,
                folder_id: 10,
                title: 'Evento B',
            },
        ])
    })

    it('clear limpa eventos', () => {
        const store = useFolderEventsStore()

        store.events = [
            {
                id: 1,
                folder_id: 10,
                title: 'Evento',
            },
        ]

        store.clear()

        expect(store.events).toEqual([])

        expect(store.count).toBe(0)
    })
})
