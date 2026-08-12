import axios from 'axios'

import { getAccessToken, removeAccessToken } from './auth-token.js'

const apiUrl = import.meta.env.VITE_API_URL

if (!apiUrl) {
    throw new Error('VITE_API_URL não está configurada.')
}

const apiClient = axios.create({
    baseURL: apiUrl,
    headers: {
        Accept: 'application/json',
    },
    timeout: 15_000,
})

apiClient.interceptors.request.use(
    (config) => {
        const token = getAccessToken()

        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }

        return config
    },
    (error) => Promise.reject(error),
)

apiClient.interceptors.response.use(
    (response) => response,

    (error) => {
        const status = error.response?.status

        if (status === 401) {
            removeAccessToken()
        }

        return Promise.reject(error)
    },
)

export default apiClient
