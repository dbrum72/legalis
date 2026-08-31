import apiClient from './client.js'

export function getDashboard() {
    return apiClient.get('/dashboard')
}

export function markDataJudIntegrationSeen(id) {
    return apiClient.post(`/dashboard/datajud-integrations/${id}/seen`)
}
