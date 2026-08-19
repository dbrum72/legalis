import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useDashboardStore } from '@/stores/dashboard.js'

vi.mock('@/api/dashboard.js', () => ({
    getDashboard: vi.fn(),
}))

import { getDashboard } from '@/api/dashboard.js'

function emptySummary() {
    return {
        clients: 0,
        folders: 0,
        active_members: 0,
        pending_tasks: 0,
        pending_deadlines: 0,
        upcoming_events: 0,
        overdue_tasks: 0,
        overdue_deadlines: 0,
        events_today: 0,
    }
}

function emptyAttention() {
    return {
        overdue_tasks: [],
        overdue_deadlines: [],
        events_today: [],
    }
}

function emptyOperational() {
    return {
        upcoming_events: [],
        pending_deadlines: [],
        pending_tasks: [],
    }
}

function emptyMyWork() {
    return {
        pending_tasks: [],
        pending_deadlines: [],
        upcoming_events: [],
    }
}

describe('dashboard store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useDashboardStore()

        expect(store.summary).toEqual(emptySummary())

        expect(store.attention).toEqual(emptyAttention())

        expect(store.recentFolders).toEqual([])

        expect(store.operational).toEqual(emptyOperational())

        expect(store.recentActivity).toEqual([])

        expect(store.myWork).toEqual(emptyMyWork())
    })

    it('fetchDashboard carrega resumo, central de atencao, pastas recentes, listas operacionais, atividade recente e meu trabalho', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {
                    clients: 12,
                    folders: 8,
                    active_members: 4,
                    pending_tasks: 7,
                    pending_deadlines: 3,
                    upcoming_events: 5,
                    overdue_tasks: 2,
                    overdue_deadlines: 1,
                    events_today: 3,
                },

                attention: {
                    overdue_tasks: [
                        {
                            id: 401,
                            folder_id: 10,
                            title: 'Tarefa vencida',
                            priority: 'high',
                            due_at: '2026-08-18T18:00:00.000000Z',
                            status: 'pending',

                            folder: {
                                id: 10,
                                name: 'Ação indenizatória',
                                process_number: '5000000-00.2026.8.21.0001',
                            },
                        },
                    ],

                    overdue_deadlines: [
                        {
                            id: 501,
                            folder_id: 11,
                            title: 'Prazo vencido',
                            due_at: '2026-08-18T23:59:59.000000Z',
                            status: 'pending',

                            folder: {
                                id: 11,
                                name: 'Ação de cobrança',
                                process_number: null,
                            },
                        },
                    ],

                    events_today: [
                        {
                            id: 601,
                            folder_id: 12,
                            type: 'hearing',
                            title: 'Audiência hoje',
                            starts_at: '2026-08-19T19:00:00.000000Z',
                            status: 'scheduled',

                            folder: {
                                id: 12,
                                name: 'Ação revisional',
                                process_number: '5002222-33.2026.8.21.0022',
                            },
                        },
                    ],
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

                operational: {
                    upcoming_events: [
                        {
                            id: 101,
                            folder_id: 10,
                            title: 'Audiência de instrução',
                            starts_at: '2026-08-20T14:00:00.000000Z',
                        },
                    ],

                    pending_deadlines: [
                        {
                            id: 201,
                            folder_id: 10,
                            title: 'Apresentar manifestação',
                            due_at: '2026-08-21T23:59:59.000000Z',
                        },
                    ],

                    pending_tasks: [
                        {
                            id: 301,
                            folder_id: 11,
                            title: 'Revisar documentos',
                            priority: 'high',
                            due_at: '2026-08-22T18:00:00.000000Z',
                        },
                    ],
                },

                recent_activity: [
                    {
                        id: 701,
                        type: 'task',
                        title: 'Revisar contrato',
                        completed_at: '2026-08-19T18:30:00.000000Z',

                        folder: {
                            id: 10,
                            name: 'Ação indenizatória',
                            process_number: '5000000-00.2026.8.21.0001',
                        },
                    },

                    {
                        id: 702,
                        type: 'deadline',
                        title: 'Protocolar manifestação',
                        completed_at: '2026-08-19T17:30:00.000000Z',

                        folder: {
                            id: 11,
                            name: 'Ação de cobrança',
                            process_number: null,
                        },
                    },

                    {
                        id: 703,
                        type: 'event',
                        title: 'Audiência realizada',
                        completed_at: '2026-08-19T16:30:00.000000Z',

                        folder: {
                            id: 12,
                            name: 'Ação revisional',
                            process_number: '5002222-33.2026.8.21.0022',
                        },
                    },
                ],

                my_work: {
                    pending_tasks: [
                        {
                            id: 801,
                            folder_id: 10,
                            title: 'Minha tarefa',
                            priority: 'high',
                            due_at: '2026-08-20T18:00:00.000000Z',
                            status: 'pending',

                            folder: {
                                id: 10,
                                name: 'Ação indenizatória',
                                process_number: '5000000-00.2026.8.21.0001',
                            },
                        },
                    ],

                    pending_deadlines: [
                        {
                            id: 802,
                            folder_id: 11,
                            title: 'Meu prazo',
                            due_at: '2026-08-21T23:59:59.000000Z',
                            status: 'pending',

                            folder: {
                                id: 11,
                                name: 'Ação de cobrança',
                                process_number: null,
                            },
                        },
                    ],

                    upcoming_events: [
                        {
                            id: 803,
                            folder_id: 12,
                            type: 'hearing',
                            title: 'Meu compromisso',
                            starts_at: '2026-08-22T19:00:00.000000Z',
                            status: 'scheduled',
                            location: 'Fórum de Pelotas',

                            folder: {
                                id: 12,
                                name: 'Ação revisional',
                                process_number: '5002222-33.2026.8.21.0022',
                            },
                        },
                    ],
                },
            },
        })

        const store = useDashboardStore()

        const result = await store.fetchDashboard()

        expect(getDashboard).toHaveBeenCalledTimes(1)

        expect(store.summary).toEqual({
            clients: 12,
            folders: 8,
            active_members: 4,
            pending_tasks: 7,
            pending_deadlines: 3,
            upcoming_events: 5,
            overdue_tasks: 2,
            overdue_deadlines: 1,
            events_today: 3,
        })

        expect(store.attention).toEqual({
            overdue_tasks: [
                {
                    id: 401,
                    folder_id: 10,
                    title: 'Tarefa vencida',
                    priority: 'high',
                    due_at: '2026-08-18T18:00:00.000000Z',
                    status: 'pending',

                    folder: {
                        id: 10,
                        name: 'Ação indenizatória',
                        process_number: '5000000-00.2026.8.21.0001',
                    },
                },
            ],

            overdue_deadlines: [
                {
                    id: 501,
                    folder_id: 11,
                    title: 'Prazo vencido',
                    due_at: '2026-08-18T23:59:59.000000Z',
                    status: 'pending',

                    folder: {
                        id: 11,
                        name: 'Ação de cobrança',
                        process_number: null,
                    },
                },
            ],

            events_today: [
                {
                    id: 601,
                    folder_id: 12,
                    type: 'hearing',
                    title: 'Audiência hoje',
                    starts_at: '2026-08-19T19:00:00.000000Z',
                    status: 'scheduled',

                    folder: {
                        id: 12,
                        name: 'Ação revisional',
                        process_number: '5002222-33.2026.8.21.0022',
                    },
                },
            ],
        })

        expect(store.recentFolders).toHaveLength(2)

        expect(store.recentFolders[0]).toEqual({
            id: 10,
            name: 'Ação indenizatória',
            process_number: '5000000-00.2026.8.21.0001',
            created_at: '2026-08-17T10:00:00.000000Z',
        })

        expect(store.operational).toEqual({
            upcoming_events: [
                {
                    id: 101,
                    folder_id: 10,
                    title: 'Audiência de instrução',
                    starts_at: '2026-08-20T14:00:00.000000Z',
                },
            ],

            pending_deadlines: [
                {
                    id: 201,
                    folder_id: 10,
                    title: 'Apresentar manifestação',
                    due_at: '2026-08-21T23:59:59.000000Z',
                },
            ],

            pending_tasks: [
                {
                    id: 301,
                    folder_id: 11,
                    title: 'Revisar documentos',
                    priority: 'high',
                    due_at: '2026-08-22T18:00:00.000000Z',
                },
            ],
        })

        expect(store.recentActivity).toEqual([
            {
                id: 701,
                type: 'task',
                title: 'Revisar contrato',
                completed_at: '2026-08-19T18:30:00.000000Z',

                folder: {
                    id: 10,
                    name: 'Ação indenizatória',
                    process_number: '5000000-00.2026.8.21.0001',
                },
            },

            {
                id: 702,
                type: 'deadline',
                title: 'Protocolar manifestação',
                completed_at: '2026-08-19T17:30:00.000000Z',

                folder: {
                    id: 11,
                    name: 'Ação de cobrança',
                    process_number: null,
                },
            },

            {
                id: 703,
                type: 'event',
                title: 'Audiência realizada',
                completed_at: '2026-08-19T16:30:00.000000Z',

                folder: {
                    id: 12,
                    name: 'Ação revisional',
                    process_number: '5002222-33.2026.8.21.0022',
                },
            },
        ])

        expect(store.myWork).toEqual({
            pending_tasks: [
                {
                    id: 801,
                    folder_id: 10,
                    title: 'Minha tarefa',
                    priority: 'high',
                    due_at: '2026-08-20T18:00:00.000000Z',
                    status: 'pending',

                    folder: {
                        id: 10,
                        name: 'Ação indenizatória',
                        process_number: '5000000-00.2026.8.21.0001',
                    },
                },
            ],

            pending_deadlines: [
                {
                    id: 802,
                    folder_id: 11,
                    title: 'Meu prazo',
                    due_at: '2026-08-21T23:59:59.000000Z',
                    status: 'pending',

                    folder: {
                        id: 11,
                        name: 'Ação de cobrança',
                        process_number: null,
                    },
                },
            ],

            upcoming_events: [
                {
                    id: 803,
                    folder_id: 12,
                    type: 'hearing',
                    title: 'Meu compromisso',
                    starts_at: '2026-08-22T19:00:00.000000Z',
                    status: 'scheduled',
                    location: 'Fórum de Pelotas',

                    folder: {
                        id: 12,
                        name: 'Ação revisional',
                        process_number: '5002222-33.2026.8.21.0022',
                    },
                },
            ],
        })

        expect(result).toEqual({
            summary: store.summary,

            attention: store.attention,

            recent_folders: store.recentFolders,

            operational: store.operational,

            recent_activity: store.recentActivity,

            my_work: store.myWork,
        })
    })

    it('normaliza resumo ausente', async () => {
        getDashboard.mockResolvedValue({
            data: {},
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.summary).toEqual(emptySummary())
    })

    it('normaliza central de atencao ausente', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},
                recent_folders: [],
                operational: {},
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.attention).toEqual(emptyAttention())
    })

    it('normaliza colecoes invalidas da central de atencao', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {
                    overdue_tasks: null,

                    overdue_deadlines: {
                        id: 1,
                    },

                    events_today: 'valor inválido',
                },

                recent_folders: [],

                operational: {},
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.attention).toEqual(emptyAttention())
    })

    it('normaliza cada colecao da central de atencao de forma independente', async () => {
        const task = {
            id: 401,
            title: 'Tarefa vencida',
        }

        const event = {
            id: 601,
            title: 'Audiência hoje',
        }

        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {
                    overdue_tasks: [task],

                    overdue_deadlines: null,

                    events_today: [event],
                },

                recent_folders: [],

                operational: {},
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.attention).toEqual({
            overdue_tasks: [task],

            overdue_deadlines: [],

            events_today: [event],
        })
    })

    it('usa lista vazia quando recent_folders nao e array', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {
                    clients: 1,
                    folders: 2,
                    active_members: 3,
                    pending_tasks: 4,
                    pending_deadlines: 5,
                    upcoming_events: 6,
                    overdue_tasks: 7,
                    overdue_deadlines: 8,
                    events_today: 9,
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
                    pending_tasks: '9',
                    pending_deadlines: '4',
                    upcoming_events: '6',
                    overdue_tasks: '2',
                    overdue_deadlines: '1',
                    events_today: '8',
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
            pending_tasks: 9,
            pending_deadlines: 4,
            upcoming_events: 6,
            overdue_tasks: 2,
            overdue_deadlines: 1,
            events_today: 8,
        })
    })

    it('normaliza operational ausente', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {
                    clients: 1,
                    folders: 2,
                    active_members: 3,
                    pending_tasks: 4,
                    pending_deadlines: 5,
                    upcoming_events: 6,
                    overdue_tasks: 0,
                    overdue_deadlines: 0,
                    events_today: 0,
                },

                recent_folders: [],
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.operational).toEqual(emptyOperational())
    })

    it('normaliza colecoes operacionais invalidas', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {
                    clients: 1,
                    folders: 2,
                    active_members: 3,
                    pending_tasks: 4,
                    pending_deadlines: 5,
                    upcoming_events: 6,
                    overdue_tasks: 0,
                    overdue_deadlines: 0,
                    events_today: 0,
                },

                recent_folders: [],

                operational: {
                    upcoming_events: null,

                    pending_deadlines: {
                        id: 1,
                    },

                    pending_tasks: 'valor inválido',
                },
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.operational).toEqual(emptyOperational())
    })

    it('normaliza cada colecao operacional de forma independente', async () => {
        const event = {
            id: 101,
            title: 'Audiência',
        }

        const task = {
            id: 301,
            title: 'Revisar documentos',
        }

        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                recent_folders: [],

                operational: {
                    upcoming_events: [event],

                    pending_deadlines: null,

                    pending_tasks: [task],
                },
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.operational).toEqual({
            upcoming_events: [event],

            pending_deadlines: [],

            pending_tasks: [task],
        })
    })

    it('normaliza atividade recente ausente', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {},

                recent_folders: [],

                operational: {},
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.recentActivity).toEqual([])
    })

    it('normaliza atividade recente invalida', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {},

                recent_folders: [],

                operational: {},

                recent_activity: {
                    id: 701,
                    type: 'task',
                },
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.recentActivity).toEqual([])
    })

    it('preserva atividade recente quando a API retorna array', async () => {
        const recentActivity = [
            {
                id: 701,
                type: 'task',
                title: 'Revisar contrato',
                completed_at: '2026-08-19T18:30:00.000000Z',

                folder: {
                    id: 10,
                    name: 'Ação indenizatória',
                    process_number: '5000000-00.2026.8.21.0001',
                },
            },
        ]

        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {},

                recent_folders: [],

                operational: {},

                recent_activity: recentActivity,
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.recentActivity).toEqual(recentActivity)
    })

    it('normaliza meu trabalho ausente', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {},

                recent_folders: [],

                operational: {},

                recent_activity: [],
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.myWork).toEqual(emptyMyWork())
    })

    it('normaliza colecoes invalidas de meu trabalho', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {},

                recent_folders: [],

                operational: {},

                recent_activity: [],

                my_work: {
                    pending_tasks: null,

                    pending_deadlines: {
                        id: 1,
                    },

                    upcoming_events: 'valor inválido',
                },
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.myWork).toEqual(emptyMyWork())
    })

    it('normaliza cada colecao de meu trabalho de forma independente', async () => {
        const task = {
            id: 801,
            title: 'Minha tarefa',
        }

        const event = {
            id: 803,
            title: 'Meu compromisso',
        }

        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {},

                recent_folders: [],

                operational: {},

                recent_activity: [],

                my_work: {
                    pending_tasks: [task],

                    pending_deadlines: null,

                    upcoming_events: [event],
                },
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.myWork).toEqual({
            pending_tasks: [task],

            pending_deadlines: [],

            upcoming_events: [event],
        })
    })

    it('clear restaura estado inicial', () => {
        const store = useDashboardStore()

        store.summary = {
            clients: 10,
            folders: 20,
            active_members: 30,
            pending_tasks: 40,
            pending_deadlines: 50,
            upcoming_events: 60,
            overdue_tasks: 70,
            overdue_deadlines: 80,
            events_today: 90,
        }

        store.attention = {
            overdue_tasks: [
                {
                    id: 1,
                },
            ],

            overdue_deadlines: [
                {
                    id: 2,
                },
            ],

            events_today: [
                {
                    id: 3,
                },
            ],
        }

        store.recentFolders = [
            {
                id: 1,
                name: 'Pasta teste',
            },
        ]

        store.operational = {
            upcoming_events: [
                {
                    id: 4,
                },
            ],

            pending_deadlines: [
                {
                    id: 5,
                },
            ],

            pending_tasks: [
                {
                    id: 6,
                },
            ],
        }

        store.recentActivity = [
            {
                id: 7,
                type: 'task',
                title: 'Atividade concluída',
            },
        ]

        store.myWork = {
            pending_tasks: [
                {
                    id: 8,
                },
            ],

            pending_deadlines: [
                {
                    id: 9,
                },
            ],

            upcoming_events: [
                {
                    id: 10,
                },
            ],
        }

        store.clear()

        expect(store.summary).toEqual(emptySummary())

        expect(store.attention).toEqual(emptyAttention())

        expect(store.recentFolders).toEqual([])

        expect(store.operational).toEqual(emptyOperational())

        expect(store.recentActivity).toEqual([])

        expect(store.myWork).toEqual(emptyMyWork())
    })
})
