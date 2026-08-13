import apiClient from './client.js'

export function listMaritalStatuses() {
  return apiClient.get('/marital-statuses')
}