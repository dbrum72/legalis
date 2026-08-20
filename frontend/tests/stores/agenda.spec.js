import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { getAgenda } from '@/api/agenda.js'

import { useAgendaStore } from '@/stores/agenda.js'

vi.mock('@/api/agenda.js', () => ({
    getAgenda: vi.fn(),
}))

function emptyPeriod() {
    return {
        start: null,

        end: null,
    }
}

describe('agenda store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useAgendaStore()

        expect(store.period).toEqual(emptyPeriod())

        expect(store.items).toEqual([])
    })

    it('fetchAgenda carrega periodo e itens', async () => {
        const store = useAgendaStore()

        getAgenda.mockResolvedValue({
            data: {
                period: {
                    start: '2026-08-01',

                    end: '2026-08-31',
                },

                items: [
                    {
                        type: 'task',

                        id: 10,

                        title: 'Revisar documentos',

                        starts_at: '2026-08-10T14:00:00.000000Z',

                        ends_at: null,

                        priority: 'high',

                        event_type: null,

                        location: null,

                        status: 'pending',

                        completed_at: null,

                        folder: {
                            id: 100,

                            name: 'Ação indenizatória',

                            process_number: '5000000-00.2026.8.21.0001',
                        },
                    },

                    {
                        type: 'deadline',

                        id: 20,

                        title: 'Protocolar manifestação',

                        starts_at: '2026-08-21T23:59:59.000000Z',

                        ends_at: null,

                        priority: null,

                        event_type: null,

                        location: null,

                        status: 'pending',

                        completed_at: null,

                        folder: {
                            id: 100,

                            name: 'Ação indenizatória',

                            process_number: '5000000-00.2026.8.21.0001',
                        },
                    },

                    {
                        type: 'event',

                        id: 30,

                        title: 'Audiência de instrução',

                        starts_at: '2026-08-25T14:00:00.000000Z',

                        ends_at: '2026-08-25T15:00:00.000000Z',

                        priority: null,

                        event_type: 'hearing',

                        location: 'Fórum de Pelotas',

                        status: 'scheduled',

                        completed_at: null,

                        folder: {
                            id: 100,

                            name: 'Ação indenizatória',

                            process_number: '5000000-00.2026.8.21.0001',
                        },
                    },
                ],
            },
        })

        const result = await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(getAgenda).toHaveBeenCalledTimes(1)

        expect(getAgenda).toHaveBeenCalledWith({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.period).toEqual({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.items).toHaveLength(3)

        expect(store.items[0]).toEqual(
            expect.objectContaining({
                type: 'task',

                id: 10,

                title: 'Revisar documentos',
            }),
        )

        expect(store.items[1]).toEqual(
            expect.objectContaining({
                type: 'deadline',

                id: 20,

                title: 'Protocolar manifestação',
            }),
        )

        expect(store.items[2]).toEqual(
            expect.objectContaining({
                type: 'event',

                id: 30,

                title: 'Audiência de instrução',
            }),
        )

        expect(result).toEqual({
            period: {
                start: '2026-08-01',

                end: '2026-08-31',
            },

            items: store.items,
        })
    })

    it('normaliza periodo ausente', async () => {
        const store = useAgendaStore()

        getAgenda.mockResolvedValue({
            data: {
                items: [],
            },
        })

        await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.period).toEqual(emptyPeriod())
    })

    it('normaliza inicio ausente no periodo', async () => {
        const store = useAgendaStore()

        getAgenda.mockResolvedValue({
            data: {
                period: {
                    end: '2026-08-31',
                },

                items: [],
            },
        })

        await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.period).toEqual({
            start: null,

            end: '2026-08-31',
        })
    })

    it('normaliza fim ausente no periodo', async () => {
        const store = useAgendaStore()

        getAgenda.mockResolvedValue({
            data: {
                period: {
                    start: '2026-08-01',
                },

                items: [],
            },
        })

        await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.period).toEqual({
            start: '2026-08-01',

            end: null,
        })
    })

    it('normaliza periodo invalido', async () => {
        const store = useAgendaStore()

        getAgenda.mockResolvedValue({
            data: {
                period: 'invalid',

                items: [],
            },
        })

        await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.period).toEqual(emptyPeriod())
    })

    it('normaliza itens ausentes', async () => {
        const store = useAgendaStore()

        getAgenda.mockResolvedValue({
            data: {
                period: {
                    start: '2026-08-01',

                    end: '2026-08-31',
                },
            },
        })

        await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.items).toEqual([])
    })

    it('normaliza itens invalidos', async () => {
        const store = useAgendaStore()

        getAgenda.mockResolvedValue({
            data: {
                period: {
                    start: '2026-08-01',

                    end: '2026-08-31',
                },

                items: 'invalid',
            },
        })

        await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.items).toEqual([])
    })

    it('normaliza payload ausente', async () => {
        const store = useAgendaStore()

        getAgenda.mockResolvedValue({})

        const result = await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.period).toEqual(emptyPeriod())

        expect(store.items).toEqual([])

        expect(result).toEqual({
            period: emptyPeriod(),

            items: [],
        })
    })

    it('preserva os itens retornados pela api', async () => {
        const store = useAgendaStore()

        const items = [
            {
                type: 'task',

                id: 101,

                title: 'Preparar contestação',
            },

            {
                type: 'deadline',

                id: 102,

                title: 'Prazo recursal',
            },

            {
                type: 'event',

                id: 103,

                title: 'Audiência',
            },
        ]

        getAgenda.mockResolvedValue({
            data: {
                period: {
                    start: '2026-08-01',

                    end: '2026-08-31',
                },

                items,
            },
        })

        await store.fetchAgenda({
            start: '2026-08-01',

            end: '2026-08-31',
        })

        expect(store.items).toEqual(items)
    })

    it('propaga erro da api sem substituir o estado atual', async () => {
        const store = useAgendaStore()

        store.period = {
            start: '2026-07-01',

            end: '2026-07-31',
        }

        store.items = [
            {
                id: 50,

                type: 'task',

                title: 'Estado anterior',
            },
        ]

        const error = new Error('Falha ao carregar agenda')

        getAgenda.mockRejectedValue(error)

        await expect(
            store.fetchAgenda({
                start: '2026-08-01',

                end: '2026-08-31',
            }),
        ).rejects.toThrow('Falha ao carregar agenda')

        expect(store.period).toEqual({
            start: '2026-07-01',

            end: '2026-07-31',
        })

        expect(store.items).toEqual([
            {
                id: 50,

                type: 'task',

                title: 'Estado anterior',
            },
        ])
    })

    it('clear restaura estado inicial', () => {
        const store = useAgendaStore()

        store.period = {
            start: '2026-08-01',

            end: '2026-08-31',
        }

        store.items = [
            {
                id: 1,

                type: 'task',
            },

            {
                id: 2,

                type: 'deadline',
            },

            {
                id: 3,

                type: 'event',
            },
        ]

        store.clear()

        expect(store.period).toEqual(emptyPeriod())

        expect(store.items).toEqual([])
    })
})
