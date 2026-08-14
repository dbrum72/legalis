import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import { listQualifications } from '@/api/qualifications.js'

export const useQualificationsStore = defineStore('qualifications', () => {
    const qualifications = ref([])

    const options = computed(() =>
        qualifications.value.map((qualification) => ({
            label: qualification.name,
            value: qualification.id,
        })),
    )

    async function fetchQualifications() {
        const response = await listQualifications()

        qualifications.value = Array.isArray(response.data) ? response.data : []

        return qualifications.value
    }

    function getById(id) {
        return (
            qualifications.value.find((qualification) => Number(qualification.id) === Number(id)) ??
            null
        )
    }

    function clear() {
        qualifications.value = []
    }

    return {
        qualifications,

        options,

        fetchQualifications,
        getById,
        clear,
    }
})
