import apiClient from './client.js'

export function listFolderTasks(folderId) {
    return apiClient.get(`/folders/${folderId}/tasks`)
}

export function createFolderTask(folderId, payload) {
    return apiClient.post(`/folders/${folderId}/tasks`, payload)
}

export function completeFolderTask(folderId, taskId) {
    return apiClient.patch(`/folders/${folderId}/tasks/${taskId}/complete`)
}

export function deleteFolderTask(folderId, taskId) {
    return apiClient.delete(`/folders/${folderId}/tasks/${taskId}`)
}
