import apiClient from './client.js'

export const listRecurrences = () => apiClient.get('/payable-recurrences')
export const createRecurrence = (data) => apiClient.post('/payable-recurrences', data)
export const updateRecurrence = (id, data) => apiClient.patch(`/payable-recurrences/${id}`, data)
export const previewRecurrence = (id, through) =>
    apiClient.get(`/payable-recurrences/${id}/preview`, { params: { through } })
export const generateRecurrence = (id, through) =>
    apiClient.post(`/payable-recurrences/${id}/generate`, { through })
export const payableAlerts = () => apiClient.get('/payables/alerts')
