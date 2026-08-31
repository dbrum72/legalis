import { ref } from 'vue'

import { defineStore } from 'pinia'

import {
    getDashboard as getDashboardRequest,
    markDataJudIntegrationSeen as markDataJudIntegrationSeenRequest,
} from '@/api/dashboard.js'

function emptySummary() {
    return {
        clients: 0,

        folders: 0,

        active_members: 0,

        pending_tasks: 0,

        pending_deadlines: 0,

        upcoming_events: 0,

        overdue_tasks: 0,

        overdue_deadlines: 0
    }
}

function emptyAttention() {
    return {
        overdue_tasks: [],

        overdue_deadlines: []
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

    const todayAgenda = ref([])

    const recentFolders = ref([])

    const recentActivity = ref([])

    const myWork = ref(emptyMyWork())

    const unseenDataJudIntegrations = ref([])

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

            overdue_deadlines: Number(payload?.summary?.overdue_deadlines) || 0
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
        }

        /*
        |--------------------------------------------------------------------------
        | Agenda de hoje
        |--------------------------------------------------------------------------
        */

        todayAgenda.value = Array.isArray(payload?.today_agenda) ? payload.today_agenda : []

        /*
        |--------------------------------------------------------------------------
        | Pastas recentes
        |--------------------------------------------------------------------------
        */

        recentFolders.value = Array.isArray(payload?.recent_folders) ? payload.recent_folders : []

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

        unseenDataJudIntegrations.value = Array.isArray(payload?.unseen_datajud_integrations)
            ? payload.unseen_datajud_integrations
            : []

        /*
        |--------------------------------------------------------------------------
        | Contract
        |--------------------------------------------------------------------------
        */

        return {
            summary: summary.value,

            attention: attention.value,

            today_agenda: todayAgenda.value,

            recent_folders: recentFolders.value,

            recent_activity: recentActivity.value,

            my_work: myWork.value,

            unseen_datajud_integrations: unseenDataJudIntegrations.value,
        }
    }

    async function markDataJudIntegrationSeen(id) {
        await markDataJudIntegrationSeenRequest(id)

        unseenDataJudIntegrations.value = unseenDataJudIntegrations.value.filter(
            (integration) => Number(integration.id) !== Number(id),
        )
    }

    /*
    |--------------------------------------------------------------------------
    | Clear
    |--------------------------------------------------------------------------
    */

    function clear() {
        summary.value = emptySummary()

        attention.value = emptyAttention()

        todayAgenda.value = []

        recentFolders.value = []

        recentActivity.value = []

        myWork.value = emptyMyWork()

        unseenDataJudIntegrations.value = []
    }

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */

    return {
        summary,

        attention,

        todayAgenda,

        recentFolders,

        recentActivity,

        myWork,

        unseenDataJudIntegrations,

        fetchDashboard,

        markDataJudIntegrationSeen,

        clear,
    }
})
