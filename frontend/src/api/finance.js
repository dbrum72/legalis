import apiClient from './client.js'

export const getFinancialSummary = () => apiClient.get('/finance/summary')
export const exportFinancialReport = () =>
    apiClient.get('/finance/report', { responseType: 'blob' })
export const listReminderRules = () => apiClient.get('/finance/reminder-rules')
export const createReminderRule = (payload) => apiClient.post('/finance/reminder-rules', payload)
export const updateReminderRule = (id, payload) =>
    apiClient.patch(`/finance/reminder-rules/${id}`, payload)
export const deleteReminderRule = (id) => apiClient.delete(`/finance/reminder-rules/${id}`)
export const listInvoices = (params = {}) => apiClient.get('/invoices', { params })
export const exportInvoices = (params = {}) =>
    apiClient.get('/invoices/export', { params, responseType: 'blob' })
export const getInvoice = (id) => apiClient.get(`/invoices/${id}`)
export const createInvoice = (payload) => apiClient.post('/invoices', payload)
export const updateInvoice = (id, payload) => apiClient.patch(`/invoices/${id}`, payload)
export const deleteInvoice = (id) => apiClient.delete(`/invoices/${id}`)
export const createPayment = (invoiceId, payload) =>
    apiClient.post(`/invoices/${invoiceId}/payments`, payload)
export const getPaymentReceipt = (invoiceId, paymentId) =>
    apiClient.get(`/invoices/${invoiceId}/payments/${paymentId}/receipt`, { responseType: 'blob' })
export const sendPaymentReceipt = (invoiceId, paymentId, payload) =>
    apiClient.post(`/invoices/${invoiceId}/payments/${paymentId}/receipt`, payload)
export const cancelPayment = (invoiceId, paymentId, payload) =>
    apiClient.post(`/invoices/${invoiceId}/payments/${paymentId}/cancel`, payload)
export const createInvoiceReminder = (invoiceId, payload) =>
    apiClient.post(`/invoices/${invoiceId}/reminders`, payload)
export const createInvoiceInstallment = (invoiceId, payload) =>
    apiClient.post(`/invoices/${invoiceId}/installments`, payload)
export const cancelInvoice = (invoiceId, payload) =>
    apiClient.post(`/invoices/${invoiceId}/cancel`, payload)
export const listPayables = (params = {}) => apiClient.get('/payables', { params })
export const createPayable = (payload) => apiClient.post('/payables', payload)
export const updatePayable = (id, payload) => apiClient.patch(`/payables/${id}`, payload)
export const deletePayable = (id) => apiClient.delete(`/payables/${id}`)
export const cancelPayable = (id, payload) => apiClient.post(`/payables/${id}/cancel`, payload)
export const createPayablePayment = (id, payload) =>
    apiClient.post(`/payables/${id}/payments`, payload)
export const cancelPayablePayment = (id, paymentId, payload) =>
    apiClient.post(`/payables/${id}/payments/${paymentId}/cancel`, payload)
