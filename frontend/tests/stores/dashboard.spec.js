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
    }
}

function emptyAttention() {
    return {
        overdue_tasks: [],
        overdue_deadlines: [],
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

        expect(store.recentActivity).toEqual([])

        expect(store.myWork).toEqual(emptyMyWork())
    })

    it('fetchDashboard carrega resumo, central de atencao, pastas recentes, atividade recente e meu trabalho', async () => {
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
        })

        expect(store.recentFolders).toHaveLength(2)

        expect(store.recentFolders[0]).toEqual({
            id: 10,
            name: 'Ação indenizatória',
            process_number: '5000000-00.2026.8.21.0001',
            created_at: '2026-08-17T10:00:00.000000Z',
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

            today_agenda: store.todayAgenda,

            recent_folders: store.recentFolders,

            recent_activity: store.recentActivity,

            my_work: store.myWork,

            unseen_datajud_integrations: store.unseenDataJudIntegrations,
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
                },

                recent_folders: [],
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
                },

                recent_folders: [],
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.attention).toEqual({
            overdue_tasks: [task],

            overdue_deadlines: [],
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
        })
    })

    it('normaliza atividade recente ausente', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},

                attention: {},

                recent_folders: [],
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
        }

        store.recentFolders = [
            {
                id: 1,
                name: 'Pasta teste',
            },
        ]

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

        store.todayAgenda = [
            {
                kind: 'task',
                id: 11,
                title: 'Tarefa de hoje',
            },
        ]

        store.clear()

        expect(store.summary).toEqual(emptySummary())

        expect(store.attention).toEqual(emptyAttention())

        expect(store.recentFolders).toEqual([])

        expect(store.recentActivity).toEqual([])

        expect(store.myWork).toEqual(emptyMyWork())

        expect(store.todayAgenda).toEqual([])
    })

    it('carrega a agenda do dia consolidada', async () => {
        const todayAgenda = [
            {
                kind: 'task',
                id: 901,
                title: 'Revisar contestação',
                scheduled_at: '2026-08-24T15:00:00.000000Z',
                priority: 'high',

                folder: {
                    id: 10,
                    name: 'Ação indenizatória',
                    process_number: '5000000-00.2026.8.21.0001',
                },
            },

            {
                kind: 'deadline',
                id: 902,
                title: 'Protocolar manifestação',
                scheduled_at: '2026-08-24T18:00:00.000000Z',

                folder: {
                    id: 11,
                    name: 'Ação revisional',
                    process_number: '5002222-33.2026.8.21.0022',
                },
            },

            {
                kind: 'event',
                id: 903,
                title: 'Audiência de instrução',
                scheduled_at: '2026-08-24T19:00:00.000000Z',
                type: 'hearing',
                location: 'Fórum de Pelotas',

                folder: {
                    id: 12,
                    name: 'Ação de alimentos',
                    process_number: '5003333-44.2026.8.21.0022',
                },
            },
        ]

        getDashboard.mockResolvedValue({
            data: {
                summary: {},
                attention: {},
                today_agenda: todayAgenda,
                recent_folders: [],
                recent_activity: [],
                my_work: {},
            },
        })

        const store = useDashboardStore()

        const result = await store.fetchDashboard()

        expect(store.todayAgenda).toEqual(todayAgenda)

        expect(result.today_agenda).toEqual(todayAgenda)
    })

    it('normaliza agenda do dia ausente', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},
                attention: {},
                recent_folders: [],
                recent_activity: [],
                my_work: {},
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.todayAgenda).toEqual([])
    })

    it('normaliza agenda do dia invalida', async () => {
        getDashboard.mockResolvedValue({
            data: {
                summary: {},
                attention: {},
                today_agenda: {
                    kind: 'task',
                    id: 901,
                },
                recent_folders: [],
                recent_activity: [],
                my_work: {},
            },
        })

        const store = useDashboardStore()

        await store.fetchDashboard()

        expect(store.todayAgenda).toEqual([])
    })
})
