import apiClient from './client.js'

export function listClients() {
    return apiClient.get('/clients')
}

export function getClient(id) {
    return apiClient.get(`/clients/${id}`)
}

export function createClient(payload) {
    return apiClient.post('/clients', payload)
}

export function updateClient(id, payload) {
    return apiClient.patch(`/clients/${id}`, payload)
}

export function deleteClient(id) {
    return apiClient.delete(`/clients/${id}`)
}
