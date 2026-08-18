import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import {
    completeFolderEvent as completeFolderEventRequest,
    createFolderEvent as createFolderEventRequest,
    deleteFolderEvent as deleteFolderEventRequest,
    listFolderEvents as listFolderEventsRequest,
} from '@/api/folder-events.js'

export const useFolderEventsStore = defineStore('folder-events', () => {
    const events = ref([])

    const count = computed(() => events.value.length)

    function sortEvents() {
        events.value = [...events.value].sort((left, right) => {
            const leftTime = new Date(left.starts_at).getTime()

            const rightTime = new Date(right.starts_at).getTime()

            return leftTime - rightTime
        })
    }

    async function fetchEvents(folderId) {
        const response = await listFolderEventsRequest(folderId)

        events.value = Array.isArray(response.data) ? response.data : []

        return events.value
    }

    async function createEvent(folderId, payload) {
        const response = await createFolderEventRequest(folderId, payload)

        const created = response.data

        events.value.push(created)

        sortEvents()

        return created
    }

    async function completeEvent(folderId, eventId) {
        const response = await completeFolderEventRequest(folderId, eventId)

        const completed = response.data

        const index = events.value.findIndex((event) => Number(event.id) === Number(eventId))

        if (index !== -1) {
            events.value[index] = completed
        }

        return completed
    }

    async function removeEvent(folderId, eventId) {
        await deleteFolderEventRequest(folderId, eventId)

        events.value = events.value.filter((event) => Number(event.id) !== Number(eventId))
    }

    function clear() {
        events.value = []
    }

    return {
        events,
        count,

        fetchEvents,
        createEvent,
        completeEvent,
        removeEvent,

        clear,
    }
})
