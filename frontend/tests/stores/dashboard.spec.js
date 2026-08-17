import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useDashboardStore } from '@/stores/dashboard.js'

vi.mock('@/api/dashboard.js', () => ({
    getDashboard: vi.fn(),
}))

import { getDashboard } from '@/api/dashboard.js'

describe('dashboard store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useDashboardStore()

        expect(store.summary).toEqual({
            clients: 0,
            folders: 0,
            active_members: 0,
        })

        expect(store.recentFolders).toEqual([])
    })

    it('fetchDashboard carrega resumo e pastas recentes', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {
                    clients: 12,
                    folders: 8,
                    active_members: 4,
                },

                recent_folders: [
                    {
                        id: 10,

                        name: 'Ação indenizatória',

                        process_number: '5000000-00.2026.8.21.0001',

                        created_at: '2026-08-17T10:00:00.000000Z',
                    },
                    {
                        id: 11,

                        name: 'Atendimento extrajudicial',

                        process_number: null,

                        created_at: '2026-08-16T10:00:00.000000Z',
                    },
                ],
            },
        })

        const store = useDashboardStore()

        const result = await store.fetchDashboard()

        expect(getDashboard).toHaveBeenCalledTimes(1)

        expect(store.summary).toEqual({
            clients: 12,
            folders: 8,
            active_members: 4,
        })

        expect(store.recentFolders).toHaveLength(2)

        expect(store.recentFolders[0]).toEqual({
            id: 10,

            name: 'Ação indenizatória',

            process_number: '5000000-00.2026.8.21.0001',

            created_at: '2026-08-17T10:00:00.000000Z',
        })

        expect(result).toEqual({
            summary: {
                clients: 12,
                folders: 8,
                active_members: 4,
            },

            recent_folders: store.recentFolders,
        })
    })

    it('normaliza resumo ausente', async () => {
        getDashboard.mockResolvedValue({
            data: {},
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.summary).toEqual({
            clients: 0,
            folders: 0,
            active_members: 0,
        })
    })

    it('usa lista vazia quando recent_folders nao e array', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {
                    clients: 1,
                    folders: 2,
                    active_members: 3,
                },

                recent_folders: null,
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.recentFolders).toEqual([])
    })

    it('normaliza valores numericos do resumo', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {
                    clients: '7',
                    folders: '5',
                    active_members: '3',
                },

                recent_folders: [],
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.summary).toEqual({
            clients: 7,
            folders: 5,
            active_members: 3,
        })
    })

    it('clear restaura estado inicial', () => {
        const store = useDashboardStore()

        store.summary = {
            clients: 10,
            folders: 20,
            active_members: 30,
        }

        store.recentFolders = [
            {
                id: 1,
                name: 'Pasta teste',
            },
        ]

        store.clear()

        expect(store.summary).toEqual({
            clients: 0,
            folders: 0,
            active_members: 0,
        })

        expect(store.recentFolders).toEqual([])
    })
})
