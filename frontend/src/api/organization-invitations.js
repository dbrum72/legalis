import apiClient from './client.js'
import publicApiClient from './public-client.js'

const BASE_PATH = '/organization-invitations'

export async function createOrganizationInvitation(payload) {
    const response = await apiClient.post(BASE_PATH, payload)

    return response.data
}

export async function getInvitationAcceptance(token) {
    const response = await publicApiClient.get(`${BASE_PATH}/accept/${encodeURIComponent(token)}`)

    return response.data
}

export async function acceptOrganizationInvitation(token, payload = {}) {
    const response = await publicApiClient.post(
        `${BASE_PATH}/accept/${encodeURIComponent(token)}`,
        payload,
    )

    return response.data
}
