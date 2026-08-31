import apiClient from './client.js'

export function listFolders() {
    return apiClient.get('/folders')
}

export function getFolder(id) {
    return apiClient.get(`/folders/${id}`)
}

export function createFolder(payload) {
    return apiClient.post('/folders', payload)
}

export function updateFolder(id, payload) {
    return apiClient.patch(`/folders/${id}`, payload)
}

export function deleteFolder(id) {
    return apiClient.delete(`/folders/${id}`)
}

export function syncFolderWithDataJud(id) {
    return apiClient.post(`/folders/${id}/datajud/sync`)
}
