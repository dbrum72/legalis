import apiClient from './client.js'

const BASE_PATH = '/organization-roles'

export function listOrganizationRoles() {
    return apiClient.get(BASE_PATH)
}
