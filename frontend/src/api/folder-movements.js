import apiClient from './client.js'

export function listFolderMovements(folderId) {
    return apiClient.get(`/folders/${folderId}/movements`)
}

export function createFolderMovement(folderId, payload) {
    return apiClient.post(`/folders/${folderId}/movements`, payload)
}

export function deleteFolderMovement(folderId, movementId) {
    return apiClient.delete(`/folders/${folderId}/movements/${movementId}`)
}
