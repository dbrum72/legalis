import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import {
    getPublication,
    linkPublication,
    listPublications,
    reviewPublication,
} from '@/api/publications.js'

export const usePublicationsStore = defineStore('publications', () => {
    const publications = ref([])
    const publication = ref(null)
    const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
    const fetching = ref(false)

    const count = computed(() => publications.value.length)

    function replace(updated) {
        const index = publications.value.findIndex((item) => Number(item.id) === Number(updated.id))

        if (index !== -1) publications.value[index] = { ...publications.value[index], ...updated }
        if (Number(publication.value?.id) === Number(updated.id)) publication.value = updated

        return updated
    }

    async function fetchPublications(filters = {}) {
        fetching.value = true

        try {
            const response = await listPublications(filters)
            const payload = response.data ?? {}

            publications.value = Array.isArray(payload.data) ? payload.data : []
            pagination.value = {
                currentPage: payload.current_page ?? 1,
                lastPage: payload.last_page ?? 1,
                total: payload.total ?? publications.value.length,
            }

            return publications.value
        } finally {
            fetching.value = false
        }
    }

    async function fetchPublication(id) {
        const response = await getPublication(id)
        publication.value = response.data
        return publication.value
    }

    async function link(id, folderId) {
        const response = await linkPublication(id, folderId)
        return replace(response.data)
    }

    async function review(id, reviewStatus) {
        const response = await reviewPublication(id, reviewStatus)
        return replace(response.data)
    }

    function clear() {
        publications.value = []
        publication.value = null
        pagination.value = { currentPage: 1, lastPage: 1, total: 0 }
        fetching.value = false
    }

    return {
        publications, publication, pagination, fetching, count,
        fetchPublications, fetchPublication, link, review, clear,
    }
})
