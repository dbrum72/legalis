import apiClient from './client.js'

export const getFinancialSummary = () => apiClient.get('/finance/summary')
export const listInvoices = (params = {}) => apiClient.get('/invoices', { params })
export const createInvoice = (payload) => apiClient.post('/invoices', payload)
export const deleteInvoice = (id) => apiClient.delete(`/invoices/${id}`)
export const createPayment = (invoiceId, payload) => apiClient.post(`/invoices/${invoiceId}/payments`, payload)
export const createInvoiceInstallment = (invoiceId, payload) => apiClient.post(`/invoices/${invoiceId}/installments`, payload)
