import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import {
    deleteFolderDocument as deleteFolderDocumentRequest,
    downloadFolderDocument as downloadFolderDocumentRequest,
    listFolderDocuments as listFolderDocumentsRequest,
    uploadFolderDocument as uploadFolderDocumentRequest,
} from '@/api/folder-documents.js'

export const useFolderDocumentsStore = defineStore('folder-documents', () => {
    const documents = ref([])

    const count = computed(() => documents.value.length)

    async function fetchDocuments(folderId) {
        const response = await listFolderDocumentsRequest(folderId)

        documents.value = Array.isArray(response.data) ? response.data : []

        return documents.value
    }

    async function uploadDocument(folderId, payload) {
        const response = await uploadFolderDocumentRequest(folderId, payload)

        const created = response.data

        documents.value.push(created)

        return created
    }

    async function downloadDocument(folderId, documentId) {
        const response = await downloadFolderDocumentRequest(folderId, documentId)

        return response.data
    }

    async function removeDocument(folderId, documentId) {
        await deleteFolderDocumentRequest(folderId, documentId)

        documents.value = documents.value.filter(
            (document) => Number(document.id) !== Number(documentId),
        )
    }

    function clear() {
        documents.value = []
    }

    return {
        documents,
        count,

        fetchDocuments,
        uploadDocument,
        downloadDocument,
        removeDocument,

        clear,
    }
})
