import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import {
    login as loginRequest,
    logout as logoutRequest,
    me as meRequest,
    refresh as refreshRequest,
} from '@/api/auth.js'

import { getAccessToken, removeAccessToken, setAccessToken } from '@/api/auth-token.js'

export const useAuthStore = defineStore('auth', () => {
    const token = ref(null)
    const user = ref(null)
    const roles = ref([])
    const permissions = ref([])
    const hydrated = ref(false)

    const isAuthenticated = computed(() => Boolean(token.value && user.value))

    const userName = computed(() => user.value?.name ?? '')

    const userEmail = computed(() => user.value?.email ?? '')

    function hasRole(role) {
        return roles.value.includes(role)
    }

    function hasPermission(permission) {
        return permissions.value.includes(permission)
    }

    function applyAuthPayload(payload) {
        const accessToken = payload?.access_token ?? payload?.token ?? null

        token.value = accessToken
        user.value = payload?.user ?? null

        roles.value = Array.isArray(payload?.roles) ? payload.roles : []

        permissions.value = Array.isArray(payload?.permissions) ? payload.permissions : []

        if (accessToken) {
            setAccessToken(accessToken)
        } else {
            removeAccessToken()
        }
    }

    function clearAuth() {
        token.value = null
        user.value = null
        roles.value = []
        permissions.value = []

        removeAccessToken()
    }

    function restoreToken() {
        token.value = getAccessToken()
        hydrated.value = true

        return token.value
    }

    async function login(credentials) {
        const response = await loginRequest(credentials)

        applyAuthPayload(response.data)

        return response.data
    }

    async function fetchMe() {
        const response = await meRequest()
        const payload = response.data

        user.value = payload?.user ?? null

        roles.value = Array.isArray(payload?.roles) ? payload.roles : []

        permissions.value = Array.isArray(payload?.permissions) ? payload.permissions : []

        return payload
    }

    async function refresh() {
        const response = await refreshRequest()

        applyAuthPayload(response.data)

        return response.data
    }

    async function logout() {
        try {
            if (token.value) {
                await logoutRequest()
            }
        } finally {
            clearAuth()
        }
    }

    async function hydrate() {
        if (hydrated.value) {
            return
        }

        restoreToken()

        if (!token.value) {
            return
        }

        try {
            await fetchMe()
        } catch {
            clearAuth()
        }
    }

    return {
        token,
        user,
        roles,
        permissions,
        hydrated,

        isAuthenticated,
        userName,
        userEmail,

        hasRole,
        hasPermission,

        applyAuthPayload,
        clearAuth,
        restoreToken,

        login,
        fetchMe,
        refresh,
        logout,
        hydrate,
    }
})
