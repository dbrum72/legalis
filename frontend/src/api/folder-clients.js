import apiClient from './client.js'

export function createFolderClient(folderId, payload) {
    return apiClient.post(`/folders/${folderId}/clients`, payload)
}

export function updateFolderClient(folderId, folderClientId, payload) {
    return apiClient.patch(`/folders/${folderId}/clients/${folderClientId}`, payload)
}

export function deleteFolderClient(folderId, folderClientId) {
    return apiClient.delete(`/folders/${folderId}/clients/${folderClientId}`)
}
