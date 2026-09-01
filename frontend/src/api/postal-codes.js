import apiClient from './client.js'

export function getAddressByPostalCode(postalCode) {
    return apiClient.get(`/postal-codes/${encodeURIComponent(postalCode)}`)
}
