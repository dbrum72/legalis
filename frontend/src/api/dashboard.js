import apiClient from './client.js'

export function getDashboard() {
    return apiClient.get('/dashboard')
}