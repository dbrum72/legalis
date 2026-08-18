import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import {
    createFolderMovement as createFolderMovementRequest,
    deleteFolderMovement as deleteFolderMovementRequest,
    listFolderMovements as listFolderMovementsRequest,
} from '@/api/folder-movements.js'

export const useFolderMovementsStore = defineStore('folder-movements', () => {
    const movements = ref([])

    const count = computed(() => movements.value.length)

    async function fetchMovements(folderId) {
        const response = await listFolderMovementsRequest(folderId)

        movements.value = Array.isArray(response.data) ? response.data : []

        return movements.value
    }

    async function createMovement(folderId, payload) {
        const response = await createFolderMovementRequest(folderId, payload)

        const created = response.data

        movements.value.unshift(created)

        return created
    }

    async function removeMovement(folderId, movementId) {
        await deleteFolderMovementRequest(folderId, movementId)

        movements.value = movements.value.filter(
            (movement) => Number(movement.id) !== Number(movementId),
        )
    }

    function clear() {
        movements.value = []
    }

    return {
        movements,
        count,

        fetchMovements,
        createMovement,
        removeMovement,

        clear,
    }
})
