import apiClient from './client.js'

export function listFolderDeadlines(folderId) {
    return apiClient.get(`/folders/${folderId}/deadlines`)
}

export function createFolderDeadline(folderId, payload) {
    return apiClient.post(`/folders/${folderId}/deadlines`, payload)
}

export function completeFolderDeadline(folderId, deadlineId) {
    return apiClient.patch(`/folders/${folderId}/deadlines/${deadlineId}/complete`)
}

export function deleteFolderDeadline(folderId, deadlineId) {
    return apiClient.delete(`/folders/${folderId}/deadlines/${deadlineId}`)
}
