import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useAuthStore } from '@/stores/auth.js'
import { authGuard } from '@/router/guards/auth.js'

vi.mock('@/api/auth-token.js', () => ({
    getAccessToken: vi.fn(),
    setAccessToken: vi.fn(),
    removeAccessToken: vi.fn(),
}))

vi.mock('@/api/auth.js', () => ({
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    refresh: vi.fn(),
}))

function createRoute({ fullPath = '/', requiresAuth = false, guestOnly = false } = {}) {
    return {
        fullPath,

        matched: [
            {
                meta: {
                    requiresAuth,
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
                fullPath: '/',
                requiresAuth: true,
            }),
        )

        expect(result).toEqual({
            name: 'login',
            query: {
                redirect: '/',
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
            }),
        )

        expect(result).toEqual({
            name: 'login',
            query: {
                redirect: '/playground?tab=forms',
            },
        })
    })

    it('permite rota protegida para usuário autenticado', async () => {
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

    it('redireciona usuário autenticado para dashboard ao acessar rota guestOnly', async () => {
        const store = useAuthStore()

        store.hydrated = true
        store.token = 'jwt-token'
        store.user = {
            id: 1,
            name: 'Super Admin',
        }

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
