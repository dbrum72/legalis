import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useAuthStore } from '@/stores/auth.js'

import { authGuard } from '@/router/guards/auth.js'

vi.mock('@/api/auth-token.js', () => ({
    getAccessToken: vi.fn(),
    setAccessToken: vi.fn(),
    removeAccessToken: vi.fn(),
}))

vi.mock('@/api/tenant.js', () => ({
    getCurrentTenant: vi.fn(),
    setCurrentTenant: vi.fn(),
    removeCurrentTenant: vi.fn(),
}))

vi.mock('@/api/auth.js', () => ({
    context: vi.fn(),
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    refresh: vi.fn(),
}))

function createRoute({
    fullPath = '/',
    requiresAuth = false,
    requiresOrganization = false,
    guestOnly = false,
} = {}) {
    return {
        fullPath,

        matched: [
            {
                meta: {
                    requiresAuth,
                    requiresOrganization,
                    guestOnly,
                },
            },
        ],
    }
}

describe('auth guard', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('permite rota pública para usuário não autenticado', async () => {
        const store = useAuthStore()

        store.hydrated = true

        const result = await authGuard(createRoute())

        expect(result).toBe(true)
    })

    it('redireciona usuário não autenticado para login', async () => {
        const store = useAuthStore()

        store.hydrated = true

        const result = await authGuard(
            createRoute({
                fullPath: '/clients',

                requiresAuth: true,

                requiresOrganization: true,
            }),
        )

        expect(result).toEqual({
            name: 'login',

            query: {
                redirect: '/clients',
            },
        })
    })

    it('preserva destino original no redirect', async () => {
        const store = useAuthStore()

        store.hydrated = true

        const result = await authGuard(
            createRoute({
                fullPath: '/playground?tab=forms',

                requiresAuth: true,

                requiresOrganization: true,
            }),
        )

        expect(result).toEqual({
            name: 'login',

            query: {
                redirect: '/playground?tab=forms',
            },
        })
    })

    it('permite rota autenticada que não exige organização', async () => {
        const store = useAuthStore()

        store.hydrated = true

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Super Admin',
        }

        const result = await authGuard(
            createRoute({
                requiresAuth: true,
            }),
        )

        expect(result).toBe(true)
    })

    it('redireciona usuário autenticado sem contexto para seleção de organização', async () => {
        const store = useAuthStore()

        store.hydrated = true

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Super Admin',
        }

        store.contextLoaded = false

        const result = await authGuard(
            createRoute({
                fullPath: '/clients',

                requiresAuth: true,

                requiresOrganization: true,
            }),
        )

        expect(result).toEqual({
            name: 'organizations.select',

            query: {
                redirect: '/clients',
            },
        })
    })

    it('permite rota operacional com contexto carregado', async () => {
        const store = useAuthStore()

        store.hydrated = true

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Super Admin',
        }

        store.organization = {
            id: 10,
            slug: 'org-a',
        }

        store.contextLoaded = true

        const result = await authGuard(
            createRoute({
                requiresAuth: true,

                requiresOrganization: true,
            }),
        )

        expect(result).toBe(true)
    })

    it('redireciona usuário autenticado com contexto para dashboard ao acessar guestOnly', async () => {
        const store = useAuthStore()

        store.hydrated = true

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Super Admin',
        }

        store.contextLoaded = true

        const result = await authGuard(
            createRoute({
                fullPath: '/login',

                guestOnly: true,
            }),
        )

        expect(result).toEqual({
            name: 'dashboard',
        })
    })

    it('redireciona usuário autenticado sem contexto para seleção ao acessar guestOnly', async () => {
        const store = useAuthStore()

        store.hydrated = true

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Super Admin',
        }

        store.contextLoaded = false

        const result = await authGuard(
            createRoute({
                fullPath: '/login',

                guestOnly: true,
            }),
        )

        expect(result).toEqual({
            name: 'organizations.select',
        })
    })

    it('permite rota guestOnly para usuário não autenticado', async () => {
        const store = useAuthStore()

        store.hydrated = true

        const result = await authGuard(
            createRoute({
                guestOnly: true,
            }),
        )

        expect(result).toBe(true)
    })
})
