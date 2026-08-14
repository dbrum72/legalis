import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import {
    createFolder as createFolderRequest,
    deleteFolder as deleteFolderRequest,
    getFolder as getFolderRequest,
    listFolders as listFoldersRequest,
    updateFolder as updateFolderRequest,
} from '@/api/folders.js'

import { createFolderClient, deleteFolderClient, updateFolderClient } from '@/api/folder-clients.js'

export const useFoldersStore = defineStore('folders', () => {
    const folders = ref([])
    const folder = ref(null)

    const count = computed(() => folders.value.length)

    const folderClients = computed(() =>
        Array.isArray(folder.value?.folder_clients) ? folder.value.folder_clients : [],
    )

    function getById(id) {
        return folders.value.find((item) => Number(item.id) === Number(id)) ?? null
    }

    async function fetchFolders() {
        const response = await listFoldersRequest()

        folders.value = Array.isArray(response.data) ? response.data : []

        return folders.value
    }

    async function fetchFolder(id) {
        const response = await getFolderRequest(id)

        folder.value = response.data

        return folder.value
    }

    async function create(payload) {
        const response = await createFolderRequest(payload)

        const created = response.data

        folders.value.push(created)

        return created
    }

    async function update(id, payload) {
        const response = await updateFolderRequest(id, payload)

        const updated = response.data

        const index = folders.value.findIndex((item) => Number(item.id) === Number(id))

        if (index !== -1) {
            folders.value[index] = updated
        }

        if (Number(folder.value?.id) === Number(id)) {
            folder.value = updated
        }

        return updated
    }

    async function remove(id) {
        await deleteFolderRequest(id)

        folders.value = folders.value.filter((item) => Number(item.id) !== Number(id))

        if (Number(folder.value?.id) === Number(id)) {
            folder.value = null
        }
    }

    async function addClient(folderId, payload) {
        const response = await createFolderClient(folderId, payload)

        const created = response.data

        if (Number(folder.value?.id) === Number(folderId)) {
            if (!Array.isArray(folder.value.folder_clients)) {
                folder.value.folder_clients = []
            }

            folder.value.folder_clients.push(created)
        }

        return created
    }

    async function updateClientQualification(folderId, folderClientId, payload) {
        const response = await updateFolderClient(folderId, folderClientId, payload)

        const updated = response.data

        if (
            Number(folder.value?.id) === Number(folderId) &&
            Array.isArray(folder.value.folder_clients)
        ) {
            const index = folder.value.folder_clients.findIndex(
                (item) => Number(item.id) === Number(folderClientId),
            )

            if (index !== -1) {
                folder.value.folder_clients[index] = updated
            }
        }

        return updated
    }

    async function removeClient(folderId, folderClientId) {
        await deleteFolderClient(folderId, folderClientId)

        if (
            Number(folder.value?.id) === Number(folderId) &&
            Array.isArray(folder.value.folder_clients)
        ) {
            folder.value.folder_clients = folder.value.folder_clients.filter(
                (item) => Number(item.id) !== Number(folderClientId),
            )
        }
    }

    function clearCurrent() {
        folder.value = null
    }

    function clear() {
        folders.value = []
        folder.value = null
    }

    return {
        folders,
        folder,

        count,
        folderClients,

        getById,

        fetchFolders,
        fetchFolder,

        create,
        update,
        remove,

        addClient,
        updateClientQualification,
        removeClient,

        clearCurrent,
        clear,
    }
})
