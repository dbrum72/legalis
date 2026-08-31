import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import {
    createMonitoredBarRegistration,
    listMonitoredBarRegistrations,
    syncMonitoredBarRegistration,
    updateMonitoredBarRegistration,
} from '@/api/monitored-bar-registrations.js'

export const useMonitoredBarRegistrationsStore = defineStore('monitored-bar-registrations', () => {
    const registrations = ref([])
    const fetching = ref(false)
    const syncingId = ref(null)
    const count = computed(() => registrations.value.length)

    function replace(updated) {
        const index = registrations.value.findIndex((item) => Number(item.id) === Number(updated.id))
        if (index !== -1) registrations.value[index] = { ...registrations.value[index], ...updated }
        return updated
    }

    async function fetchRegistrations() {
        fetching.value = true
        try {
            const response = await listMonitoredBarRegistrations()
            registrations.value = Array.isArray(response.data) ? response.data : []
            return registrations.value
        } finally {
            fetching.value = false
        }
    }

    async function create(payload) {
        const response = await createMonitoredBarRegistration(payload)
        registrations.value.push(response.data)
        return response.data
    }

    async function update(id, payload) {
        const response = await updateMonitoredBarRegistration(id, payload)
        return replace(response.data)
    }

    async function sync(id, payload = {}) {
        syncingId.value = Number(id)
        try {
            const response = await syncMonitoredBarRegistration(id, payload)
            return response.data
        } finally {
            syncingId.value = null
        }
    }

    function clear() {
        registrations.value = []
        fetching.value = false
        syncingId.value = null
    }

    return { registrations, fetching, syncingId, count, fetchRegistrations, create, update, sync, clear }
})
