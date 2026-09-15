import apiClient from './client.js'

export const getCashFlow = (params = {}) => apiClient.get('/finance/cash-flow', { params })
export const exportCashFlow = (params = {}) =>
    apiClient.get('/finance/cash-flow/export', { params, responseType: 'blob' })
