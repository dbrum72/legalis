import apiClient from './client.js'

const basePath = (folderId) => `/folders/${folderId}`

export const listFeeAgreements = (folderId) => apiClient.get(`${basePath(folderId)}/fee-agreements`)
export const createFeeAgreement = (folderId, payload) => apiClient.post(`${basePath(folderId)}/fee-agreements`, payload)
export const updateFeeAgreement = (folderId, id, payload) => apiClient.patch(`${basePath(folderId)}/fee-agreements/${id}`, payload)
export const deleteFeeAgreement = (folderId, id) => apiClient.delete(`${basePath(folderId)}/fee-agreements/${id}`)

export const listTimeEntries = (folderId) => apiClient.get(`${basePath(folderId)}/time-entries`)
export const createTimeEntry = (folderId, payload) => apiClient.post(`${basePath(folderId)}/time-entries`, payload)
export const deleteTimeEntry = (folderId, id) => apiClient.delete(`${basePath(folderId)}/time-entries/${id}`)

export const listExpenses = (folderId) => apiClient.get(`${basePath(folderId)}/expenses`)
export const createExpense = (folderId, payload) => apiClient.post(`${basePath(folderId)}/expenses`, payload)
export const deleteExpense = (folderId, id) => apiClient.delete(`${basePath(folderId)}/expenses/${id}`)
