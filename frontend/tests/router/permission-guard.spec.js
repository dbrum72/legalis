import { beforeEach, describe, expect, it } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useAuthStore } from '@/stores/auth.js'
import { permissionGuard } from '@/router/guards/permission.js'

function createRoute(permission = null) {
    return {
        matched: [
            {
                meta: {
                    permission,
                },
            },
        ],
    }
}

describe('permission guard', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
    })

    it('permite rota sem permission definida', () => {
        const result = permissionGuard(createRoute())

        expect(result).toBe(true)
    })

    it('permite usuário com permission necessária', () => {
        const authStore = useAuthStore()

        authStore.permissions = ['clients.view']

        const result = permissionGuard(createRoute('clients.view'))

        expect(result).toBe(true)
    })

    it('redireciona usuário sem permission para dashboard', () => {
        const authStore = useAuthStore()

        authStore.permissions = []

        const result = permissionGuard(createRoute('clients.view'))

        expect(result).toEqual({
            name: 'dashboard',
        })
    })

    it('considera permission definida em rota filha', () => {
        const authStore = useAuthStore()

        authStore.permissions = ['clients.view']

        const result = permissionGuard({
            matched: [
                {
                    meta: {
                        requiresAuth: true,
                    },
                },
                {
                    meta: {
                        permission: 'clients.view',
                    },
                },
            ],
        })

        expect(result).toBe(true)
    })
})
