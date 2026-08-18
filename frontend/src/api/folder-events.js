import apiClient from './client.js'

export function listFolderEvents(folderId) {
    return apiClient.get(`/folders/${folderId}/events`)
}

export function createFolderEvent(folderId, payload) {
    return apiClient.post(`/folders/${folderId}/events`, payload)
}

export function completeFolderEvent(folderId, eventId) {
    return apiClient.patch(`/folders/${folderId}/events/${eventId}/complete`)
}

export function deleteFolderEvent(folderId, eventId) {
    return apiClient.delete(`/folders/${folderId}/events/${eventId}`)
}
