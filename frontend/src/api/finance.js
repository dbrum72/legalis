import apiClient from './client.js'

export const getFinancialSummary = () => apiClient.get('/finance/summary')
export const listInvoices = (params = {}) => apiClient.get('/invoices', { params })
export const getInvoice = (id) => apiClient.get(`/invoices/${id}`)
export const createInvoice = (payload) => apiClient.post('/invoices', payload)
export const updateInvoice = (id, payload) => apiClient.patch(`/invoices/${id}`, payload)
export const deleteInvoice = (id) => apiClient.delete(`/invoices/${id}`)
export const createPayment = (invoiceId, payload) => apiClient.post(`/invoices/${invoiceId}/payments`, payload)
export const cancelPayment = (invoiceId, paymentId, payload) => apiClient.post(`/invoices/${invoiceId}/payments/${paymentId}/cancel`, payload)
export const createInvoiceInstallment = (invoiceId, payload) => apiClient.post(`/invoices/${invoiceId}/installments`, payload)
export const cancelInvoice = (invoiceId, payload) => apiClient.post(`/invoices/${invoiceId}/cancel`, payload)
