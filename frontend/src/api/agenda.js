import apiClient from './client.js'

export function getAgenda(params) {
    return apiClient.get('/agenda', {
        params,
    })
}