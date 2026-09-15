import api from './client.js'

export const listClassifications = () => api.get('/finance/classifications')
export const createClassification = (data) => api.post('/finance/classifications', data)
export const updateClassification = (id, data) => api.patch(`/finance/classifications/${id}`, data)
