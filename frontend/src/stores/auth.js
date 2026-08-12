import { defineStore } from 'pinia'

import {
    login as loginRequest,
    logout as logoutRequest,
    me as meRequest,
    refresh as refreshRequest,
} from '@/api/auth.js'

import { getAccessToken, removeAccessToken, setAccessToken } from '@/api/auth-token.js'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: null,
        user: null,
        roles: [],
        permissions: [],
        hydrated: false,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.user),

        userName: (state) => state.user?.name ?? '',

        userEmail: (state) => state.user?.email ?? '',

        hasRole: (state) => (role) => state.roles.includes(role),

        hasPermission: (state) => (permission) => state.permissions.includes(permission),
    },

    actions: {
        applyAuthPayload(payload) {
            const token = payload?.access_token ?? payload?.token ?? null

            this.token = token
            this.user = payload?.user ?? null
            this.roles = Array.isArray(payload?.roles) ? payload.roles : []
            this.permissions = Array.isArray(payload?.permissions) ? payload.permissions : []

            if (token) {
                setAccessToken(token)
            } else {
                removeAccessToken()
            }
        },

        clearAuth() {
            this.token = null
            this.user = null
            this.roles = []
            this.permissions = []

            removeAccessToken()
        },

        restoreToken() {
            this.token = getAccessToken()
            this.hydrated = true

            return this.token
        },

        async login(credentials) {
            const response = await loginRequest(credentials)

            this.applyAuthPayload(response.data)

            return response.data
        },

        async fetchMe() {
            const response = await meRequest()

            this.user = response.data

            return response.data
        },

        async refresh() {
            const response = await refreshRequest()

            this.applyAuthPayload(response.data)

            return response.data
        },

        async logout() {
            try {
                if (this.token) {
                    await logoutRequest()
                }
            } finally {
                this.clearAuth()
            }
        },

        async hydrate() {
            if (this.hydrated) {
                return
            }

            this.restoreToken()

            if (!this.token) {
                return
            }

            try {
                await this.fetchMe()
            } catch {
                this.clearAuth()
            }
        },
    },
})
