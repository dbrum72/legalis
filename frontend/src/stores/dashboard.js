import { ref } from 'vue'

import { defineStore } from 'pinia'

import { getDashboard as getDashboardRequest } from '@/api/dashboard.js'

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

export const useDashboardStore = defineStore('dashboard', () => {
    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    const summary = ref(emptySummary())

    const attention = ref(emptyAttention())

    const recentFolders = ref([])

    const operational = ref(emptyOperational())

    const recentActivity = ref([])

    const myWork = ref(emptyMyWork())

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    async function fetchDashboard() {
        const response = await getDashboardRequest()

        const payload = response?.data ?? {}

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        summary.value = {
            clients: Number(payload?.summary?.clients) || 0,

            folders: Number(payload?.summary?.folders) || 0,

            active_members: Number(payload?.summary?.active_members) || 0,

            pending_tasks: Number(payload?.summary?.pending_tasks) || 0,

            pending_deadlines: Number(payload?.summary?.pending_deadlines) || 0,

            upcoming_events: Number(payload?.summary?.upcoming_events) || 0,

            overdue_tasks: Number(payload?.summary?.overdue_tasks) || 0,

            overdue_deadlines: Number(payload?.summary?.overdue_deadlines) || 0,

            events_today: Number(payload?.summary?.events_today) || 0,
        }

        /*
        |--------------------------------------------------------------------------
        | Central de Atenção
        |--------------------------------------------------------------------------
        */

        attention.value = {
            overdue_tasks: Array.isArray(payload?.attention?.overdue_tasks)
                ? payload.attention.overdue_tasks
                : [],

            overdue_deadlines: Array.isArray(payload?.attention?.overdue_deadlines)
                ? payload.attention.overdue_deadlines
                : [],

            events_today: Array.isArray(payload?.attention?.events_today)
                ? payload.attention.events_today
                : [],
        }

        /*
        |--------------------------------------------------------------------------
        | Pastas recentes
        |--------------------------------------------------------------------------
        */

        recentFolders.value = Array.isArray(payload?.recent_folders) ? payload.recent_folders : []

        /*
        |--------------------------------------------------------------------------
        | Visão operacional
        |--------------------------------------------------------------------------
        */

        operational.value = {
            upcoming_events: Array.isArray(payload?.operational?.upcoming_events)
                ? payload.operational.upcoming_events
                : [],

            pending_deadlines: Array.isArray(payload?.operational?.pending_deadlines)
                ? payload.operational.pending_deadlines
                : [],

            pending_tasks: Array.isArray(payload?.operational?.pending_tasks)
                ? payload.operational.pending_tasks
                : [],
        }

        /*
        |--------------------------------------------------------------------------
        | Atividade recente
        |--------------------------------------------------------------------------
        */

        recentActivity.value = Array.isArray(payload?.recent_activity)
            ? payload.recent_activity
            : []

        /*
        |--------------------------------------------------------------------------
        | Meu trabalho
        |--------------------------------------------------------------------------
        */

        myWork.value = {
            pending_tasks: Array.isArray(payload?.my_work?.pending_tasks)
                ? payload.my_work.pending_tasks
                : [],

            pending_deadlines: Array.isArray(payload?.my_work?.pending_deadlines)
                ? payload.my_work.pending_deadlines
                : [],

            upcoming_events: Array.isArray(payload?.my_work?.upcoming_events)
                ? payload.my_work.upcoming_events
                : [],
        }

        /*
        |--------------------------------------------------------------------------
        | Contract
        |--------------------------------------------------------------------------
        */

        return {
            summary: summary.value,

            attention: attention.value,

            recent_folders: recentFolders.value,

            operational: operational.value,

            recent_activity: recentActivity.value,

            my_work: myWork.value,
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Clear
    |--------------------------------------------------------------------------
    */

    function clear() {
        summary.value = emptySummary()

        attention.value = emptyAttention()

        recentFolders.value = []

        operational.value = emptyOperational()

        recentActivity.value = []

        myWork.value = emptyMyWork()
    }

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */

    return {
        summary,

        attention,

        recentFolders,

        operational,

        recentActivity,

        myWork,

        fetchDashboard,

        clear,
    }
})
