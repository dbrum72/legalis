import apiClient from './client.js'

export const getReconciliation = (params) => apiClient.get('/finance/reconciliation', { params })
export const saveReconciliation = (kind, id, data) =>
    apiClient.put(`/finance/reconciliation/${kind}/${id}`, data)
export const closeFinancialMonth = (data) => apiClient.post('/finance/closing', data)
export const reopenFinancialMonth = (data) => apiClient.post('/finance/closing/reopen', data)
