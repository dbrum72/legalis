import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useFolderTasksStore } from '@/stores/folder-tasks.js'

vi.mock('@/api/folder-tasks.js', () => ({
    listFolderTasks: vi.fn(),
    createFolderTask: vi.fn(),
    completeFolderTask: vi.fn(),
    deleteFolderTask: vi.fn(),
}))

import {
    completeFolderTask,
    createFolderTask,
    deleteFolderTask,
    listFolderTasks,
} from '@/api/folder-tasks.js'

describe('folder tasks store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useFolderTasksStore()

        expect(store.tasks).toEqual([])

        expect(store.count).toBe(0)
    })

    it('fetchTasks popula coleção', async () => {
        listFolderTasks.mockResolvedValue({
            data: [
                {
                    id: 1,
                    folder_id: 10,
                    title: 'Revisar contestação',
                    priority: 'high',
                    due_at: '2026-08-25T18:00:00.000000Z',
                    status: 'pending',
                },

                {
                    id: 2,
                    folder_id: 10,
                    title: 'Telefonar para cliente',
                    priority: 'medium',
                    due_at: null,
                    status: 'completed',
                },
            ],
        })

        const store = useFolderTasksStore()

        const result = await store.fetchTasks(10)

        expect(listFolderTasks).toHaveBeenCalledTimes(1)

        expect(listFolderTasks).toHaveBeenCalledWith(10)

        expect(store.tasks).toHaveLength(2)

        expect(store.count).toBe(2)

        expect(result).toEqual(store.tasks)
    })

    it('fetchTasks usa array vazio quando resposta não é array', async () => {
        listFolderTasks.mockResolvedValue({
            data: null,
        })

        const store = useFolderTasksStore()

        await store.fetchTasks(10)

        expect(store.tasks).toEqual([])

        expect(store.count).toBe(0)
    })

    it('createTask adiciona tarefa e preserva ordenação', async () => {
        const store = useFolderTasksStore()

        store.tasks = [
            {
                id: 2,
                folder_id: 10,
                title: 'Tarefa concluída',
                priority: 'medium',
                due_at: null,
                status: 'completed',
            },
        ]

        const created = {
            id: 1,
            folder_id: 10,
            title: 'Tarefa pendente',
            priority: 'high',
            due_at: '2026-08-20T12:00:00.000000Z',
            status: 'pending',
        }

        createFolderTask.mockResolvedValue({
            data: created,
        })

        const payload = {
            title: 'Tarefa pendente',

            description: 'Revisar documentos.',

            priority: 'high',

            due_at: '2026-08-20T12:00',
        }

        const result = await store.createTask(10, payload)

        expect(createFolderTask).toHaveBeenCalledWith(10, payload)

        expect(store.tasks).toEqual([
            created,

            {
                id: 2,
                folder_id: 10,
                title: 'Tarefa concluída',
                priority: 'medium',
                due_at: null,
                status: 'completed',
            },
        ])

        expect(result).toEqual(created)
    })

    it('completeTask atualiza tarefa concluída na coleção', async () => {
        const store = useFolderTasksStore()

        store.tasks = [
            {
                id: 1,
                folder_id: 10,
                title: 'Telefonar para cliente',
                status: 'pending',
                completed_at: null,
            },
        ]

        const completed = {
            id: 1,
            folder_id: 10,
            title: 'Telefonar para cliente',
            status: 'completed',
            completed_at: '2026-08-18T19:00:00.000000Z',
        }

        completeFolderTask.mockResolvedValue({
            data: completed,
        })

        const result = await store.completeTask(10, 1)

        expect(completeFolderTask).toHaveBeenCalledWith(10, 1)

        expect(store.tasks).toEqual([completed])

        expect(result).toEqual(completed)
    })

    it('removeTask remove tarefa da coleção', async () => {
        deleteFolderTask.mockResolvedValue({
            data: null,
        })

        const store = useFolderTasksStore()

        store.tasks = [
            {
                id: 1,
                folder_id: 10,
                title: 'Tarefa A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Tarefa B',
            },
        ]

        await store.removeTask(10, 1)

        expect(deleteFolderTask).toHaveBeenCalledWith(10, 1)

        expect(store.tasks).toEqual([
            {
                id: 2,
                folder_id: 10,
                title: 'Tarefa B',
            },
        ])

        expect(store.count).toBe(1)
    })

    it('removeTask aceita id string', async () => {
        deleteFolderTask.mockResolvedValue({
            data: null,
        })

        const store = useFolderTasksStore()

        store.tasks = [
            {
                id: 1,
                folder_id: 10,
                title: 'Tarefa A',
            },

            {
                id: 2,
                folder_id: 10,
                title: 'Tarefa B',
            },
        ]

        await store.removeTask(10, '1')

        expect(store.tasks).toEqual([
            {
                id: 2,
                folder_id: 10,
                title: 'Tarefa B',
            },
        ])
    })

    it('clear limpa tarefas', () => {
        const store = useFolderTasksStore()

        store.tasks = [
            {
                id: 1,
                folder_id: 10,
                title: 'Tarefa',
            },
        ]

        store.clear()

        expect(store.tasks).toEqual([])

        expect(store.count).toBe(0)
    })
})
