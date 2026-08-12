import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import { useAuthStore } from '@/stores/auth.js'

vi.mock('@/api/auth.js', () => ({
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    refresh: vi.fn(),
}))

vi.mock('@/api/auth-token.js', () => ({
    getAccessToken: vi.fn(),
    setAccessToken: vi.fn(),
    removeAccessToken: vi.fn(),
}))

import { login, logout, me, refresh } from '@/api/auth.js'

import { getAccessToken, removeAccessToken, setAccessToken } from '@/api/auth-token.js'

describe('auth store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia desautenticada', () => {
        const store = useAuthStore()

        expect(store.token).toBeNull()
        expect(store.user).toBeNull()
        expect(store.roles).toEqual([])
        expect(store.permissions).toEqual([])
        expect(store.isAuthenticated).toBe(false)
    })

    it('aplica payload de autenticação', () => {
        const store = useAuthStore()

        store.applyAuthPayload({
            access_token: 'jwt-token',
            user: {
                id: 1,
                name: 'Super Admin',
                email: 'super-admin@legalis.local',
            },
            roles: ['super-admin'],
            permissions: ['clients.view'],
        })

        expect(store.token).toBe('jwt-token')
        expect(store.user.email).toBe('super-admin@legalis.local')
        expect(store.roles).toEqual(['super-admin'])
        expect(store.permissions).toEqual(['clients.view'])

        expect(setAccessToken).toHaveBeenCalledWith('jwt-token')
    })

    it('limpa autenticação', () => {
        const store = useAuthStore()

        store.token = 'jwt-token'
        store.user = {
            id: 1,
        }
        store.roles = ['super-admin']
        store.permissions = ['clients.view']

        store.clearAuth()

        expect(store.token).toBeNull()
        expect(store.user).toBeNull()
        expect(store.roles).toEqual([])
        expect(store.permissions).toEqual([])

        expect(removeAccessToken).toHaveBeenCalledTimes(1)
    })

    it('executa login e atualiza estado', async () => {
        login.mockResolvedValue({
            data: {
                access_token: 'jwt-token',
                user: {
                    id: 1,
                    name: 'Super Admin',
                    email: 'super-admin@legalis.local',
                },
                roles: ['super-admin'],
                permissions: ['clients.view'],
            },
        })

        const store = useAuthStore()

        await store.login({
            email: 'super-admin@legalis.local',
            password: 'senha',
        })

        expect(store.token).toBe('jwt-token')
        expect(store.user.name).toBe('Super Admin')
        expect(store.isAuthenticated).toBe(true)
    })

    it('restaura token persistido', () => {
        getAccessToken.mockReturnValue('persisted-token')

        const store = useAuthStore()

        store.restoreToken()

        expect(store.token).toBe('persisted-token')
        expect(store.hydrated).toBe(true)
    })

    it('fetchMe atualiza usuário', async () => {
        me.mockResolvedValue({
            data: {
                id: 1,
                name: 'Super Admin',
                email: 'super-admin@legalis.local',
            },
        })

        const store = useAuthStore()

        await store.fetchMe()

        expect(store.user.email).toBe('super-admin@legalis.local')
    })

    it('refresh substitui o token', async () => {
        refresh.mockResolvedValue({
            data: {
                access_token: 'new-token',
                user: {
                    id: 1,
                    name: 'Super Admin',
                    email: 'super-admin@legalis.local',
                },
                roles: ['super-admin'],
                permissions: ['clients.view'],
            },
        })

        const store = useAuthStore()

        await store.refresh()

        expect(store.token).toBe('new-token')

        expect(setAccessToken).toHaveBeenCalledWith('new-token')
    })

    it('logout chama api e limpa estado', async () => {
        logout.mockResolvedValue({
            data: {
                msg: 'Desconectado com sucesso',
            },
        })

        const store = useAuthStore()

        store.token = 'jwt-token'
        store.user = {
            id: 1,
        }

        await store.logout()

        expect(logout).toHaveBeenCalledTimes(1)

        expect(store.token).toBeNull()

        expect(store.user).toBeNull()
    })

    it('hydrate encerra sem token', async () => {
        getAccessToken.mockReturnValue(null)

        const store = useAuthStore()

        await store.hydrate()

        expect(store.hydrated).toBe(true)
        expect(me).not.toHaveBeenCalled()
    })

    it('hydrate carrega usuário quando existe token', async () => {
        getAccessToken.mockReturnValue('persisted-token')

        me.mockResolvedValue({
            data: {
                id: 1,
                name: 'Super Admin',
                email: 'super-admin@legalis.local',
            },
        })

        const store = useAuthStore()

        await store.hydrate()

        expect(store.token).toBe('persisted-token')

        expect(store.user.email).toBe('super-admin@legalis.local')
    })

    it('hydrate limpa autenticação quando me falha', async () => {
        getAccessToken.mockReturnValue('expired-token')

        me.mockRejectedValue({
            response: {
                status: 401,
            },
        })

        const store = useAuthStore()

        await store.hydrate()

        expect(store.token).toBeNull()
        expect(store.user).toBeNull()

        expect(removeAccessToken).toHaveBeenCalled()
    })

    it('verifica roles', () => {
        const store = useAuthStore()

        store.roles = ['super-admin']

        expect(store.hasRole('super-admin')).toBe(true)

        expect(store.hasRole('advogado-junior')).toBe(false)
    })

    it('verifica permissions', () => {
        const store = useAuthStore()

        store.permissions = ['clients.view']

        expect(store.hasPermission('clients.view')).toBe(true)

        expect(store.hasPermission('clients.delete')).toBe(false)
    })
})
