import api from './client.js'

export const listDocumentTemplates = (params) => api.get('/document-templates', { params })
export const replaceDocumentTemplate = (id, data) =>
    api.post(`/document-templates/${id}/replace`, data)
export const removeDocumentTemplate = (id) => api.delete(`/document-templates/${id}`)
export const getDocumentTemplateFields = () => api.get('/document-templates/fields')
export const importDocumentTemplate = (data) => api.post('/document-templates', data)
export const downloadDocumentTemplate = (id) =>
    api.get(`/document-templates/${id}/download`, { responseType: 'blob' })
