import { describe, expect, it } from 'vitest'

import router from '@/router/index.js'

describe('router', () => {
    it('define a home publica na raiz', () => {
        const route = router.resolve({
            name: 'home',
        })

        expect(route.path).toBe('/')

        expect(route.matched.some((record) => record.meta.requiresAuth)).toBe(false)

        expect(route.matched.some((record) => record.meta.requiresOrganization)).toBe(false)
    })

    it('define dashboard em /dashboard', () => {
        const route = router.resolve({
            name: 'dashboard',
        })

        expect(route.path).toBe('/dashboard')

        expect(route.matched.some((record) => record.meta.requiresAuth)).toBe(true)

        expect(route.matched.some((record) => record.meta.requiresOrganization)).toBe(true)
    })

    it('mantem login como rota publica exclusiva para convidados', () => {
        const route = router.resolve({
            name: 'login',
        })

        expect(route.path).toBe('/login')

        expect(route.matched.some((record) => record.meta.guestOnly)).toBe(true)
    })

    it('mantem cadastro como rota publica exclusiva para convidados', () => {
        const route = router.resolve({
            name: 'register',
        })

        expect(route.path).toBe('/register')

        expect(route.matched.some((record) => record.meta.guestOnly)).toBe(true)
    })

    it('mantem agenda dentro da area autenticada', () => {
        const route = router.resolve({
            name: 'agenda',
        })

        expect(route.path).toBe('/agenda')

        expect(route.matched.some((record) => record.meta.requiresAuth)).toBe(true)

        expect(route.matched.some((record) => record.meta.requiresOrganization)).toBe(true)
    })

    it('mantem clientes dentro da area autenticada', () => {
        const route = router.resolve({
            name: 'clients',
        })

        expect(route.path).toBe('/clients')

        expect(route.matched.some((record) => record.meta.requiresAuth)).toBe(true)
    })

    it('mantem pastas dentro da area autenticada', () => {
        const route = router.resolve({
            name: 'folders',
        })

        expect(route.path).toBe('/folders')

        expect(route.matched.some((record) => record.meta.requiresAuth)).toBe(true)
    })

    it('mantem equipe dentro da area autenticada', () => {
        const route = router.resolve({
            name: 'organization-members',
        })

        expect(route.path).toBe('/team')

        expect(route.matched.some((record) => record.meta.requiresAuth)).toBe(true)
    })
})
