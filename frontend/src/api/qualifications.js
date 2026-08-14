import apiClient from './client.js'

export function listQualifications() {
    return apiClient.get('/qualifications')
}
