import apiClient from './client.js'

export function listPublications(params = {}) {
    return apiClient.get('/legal-publications', { params })
}

export function getPublication(id) {
    return apiClient.get(`/legal-publications/${id}`)
}

export function linkPublication(id, folderId) {
    return apiClient.patch(`/legal-publications/${id}/folder`, {
        folder_id: folderId,
    })
}

export function reviewPublication(id, reviewStatus) {
    return apiClient.patch(`/legal-publications/${id}/review`, {
        review_status: reviewStatus,
    })
}
