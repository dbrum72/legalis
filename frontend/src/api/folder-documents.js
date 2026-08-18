import apiClient from './client.js'

export function listFolderDocuments(folderId) {
    return apiClient.get(`/folders/${folderId}/documents`)
}

export function uploadFolderDocument(folderId, payload) {
    return apiClient.post(`/folders/${folderId}/documents`, payload)
}

export function downloadFolderDocument(folderId, documentId) {
    return apiClient.get(`/folders/${folderId}/documents/${documentId}/download`, {
        responseType: 'blob',
    })
}

export function deleteFolderDocument(folderId, documentId) {
    return apiClient.delete(`/folders/${folderId}/documents/${documentId}`)
}
