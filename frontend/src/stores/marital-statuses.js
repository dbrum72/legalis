import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import { listMaritalStatuses } from '@/api/marital-statuses.js'

export const useMaritalStatusesStore = defineStore('marital-statuses', () => {
    const maritalStatuses = ref([])

    const options = computed(() =>
        maritalStatuses.value.map((maritalStatus) => ({
            label: maritalStatus.name,
            value: maritalStatus.id,
        })),
    )

    async function fetchMaritalStatuses() {
        const response = await listMaritalStatuses()

        maritalStatuses.value = Array.isArray(response.data) ? response.data : []

        return maritalStatuses.value
    }

    function clear() {
        maritalStatuses.value = []
    }

    return {
        maritalStatuses,
        options,

        fetchMaritalStatuses,
        clear,
    }
})
