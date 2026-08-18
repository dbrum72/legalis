import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import {
    completeFolderDeadline as completeFolderDeadlineRequest,
    createFolderDeadline as createFolderDeadlineRequest,
    deleteFolderDeadline as deleteFolderDeadlineRequest,
    listFolderDeadlines as listFolderDeadlinesRequest,
} from '@/api/folder-deadlines.js'

export const useFolderDeadlinesStore = defineStore('folder-deadlines', () => {
    const deadlines = ref([])

    const count = computed(() => deadlines.value.length)

    function sortDeadlines() {
        deadlines.value = [...deadlines.value].sort((left, right) => {
            const leftTime = new Date(left.due_at).getTime()

            const rightTime = new Date(right.due_at).getTime()

            return leftTime - rightTime
        })
    }

    async function fetchDeadlines(folderId) {
        const response = await listFolderDeadlinesRequest(folderId)

        deadlines.value = Array.isArray(response.data) ? response.data : []

        return deadlines.value
    }

    async function createDeadline(folderId, payload) {
        const response = await createFolderDeadlineRequest(folderId, payload)

        const created = response.data

        deadlines.value.push(created)

        sortDeadlines()

        return created
    }

    async function completeDeadline(folderId, deadlineId) {
        const response = await completeFolderDeadlineRequest(folderId, deadlineId)

        const completed = response.data

        const index = deadlines.value.findIndex(
            (deadline) => Number(deadline.id) === Number(deadlineId),
        )

        if (index !== -1) {
            deadlines.value[index] = completed
        }

        return completed
    }

    async function removeDeadline(folderId, deadlineId) {
        await deleteFolderDeadlineRequest(folderId, deadlineId)

        deadlines.value = deadlines.value.filter(
            (deadline) => Number(deadline.id) !== Number(deadlineId),
        )
    }

    function clear() {
        deadlines.value = []
    }

    return {
        deadlines,
        count,

        fetchDeadlines,
        createDeadline,
        completeDeadline,
        removeDeadline,

        clear,
    }
})
