import { ref } from 'vue'

import { defineStore } from 'pinia'

import { getDashboard as getDashboardRequest } from '@/api/dashboard.js'

function emptySummary() {
    return {
        clients: 0,
        folders: 0,
        active_members: 0,
    }
}

export const useDashboardStore = defineStore('dashboard', () => {
    const summary = ref(emptySummary())

    const recentFolders = ref([])

    async function fetchDashboard() {
        const response = await getDashboardRequest()

        const payload = response?.data ?? {}

        summary.value = {
            clients: Number(payload?.summary?.clients) || 0,

            folders: Number(payload?.summary?.folders) || 0,

            active_members: Number(payload?.summary?.active_members) || 0,
        }

        recentFolders.value = Array.isArray(payload?.recent_folders) ? payload.recent_folders : []

        return {
            summary: summary.value,

            recent_folders: recentFolders.value,
        }
    }

    function clear() {
        summary.value = emptySummary()

        recentFolders.value = []
    }

    return {
        summary,
        recentFolders,

        fetchDashboard,
        clear,
    }
})
