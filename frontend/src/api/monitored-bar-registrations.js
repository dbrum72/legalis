import apiClient from './client.js'

export function listMonitoredBarRegistrations() {
    return apiClient.get('/monitored-bar-registrations')
}

export function createMonitoredBarRegistration(payload) {
    return apiClient.post('/monitored-bar-registrations', payload)
}

export function updateMonitoredBarRegistration(id, payload) {
    return apiClient.patch(`/monitored-bar-registrations/${id}`, payload)
}

export function syncMonitoredBarRegistration(id, payload = {}) {
    return apiClient.post(`/monitored-bar-registrations/${id}/sync`, payload)
}
