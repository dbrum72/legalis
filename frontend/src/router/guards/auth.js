import { useAuthStore } from '@/stores/auth.js'

export async function authGuard(to) {
    const authStore = useAuthStore()

    if (!authStore.hydrated) {
        await authStore.hydrate()
    }

    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)

    const requiresOrganization = to.matched.some((record) => record.meta.requiresOrganization)

    const guestOnly = to.matched.some((record) => record.meta.guestOnly)

    if (requiresAuth && !authStore.isAuthenticated) {
        return {
            name: 'login',

            query: {
                redirect: to.fullPath,
            },
        }
    }

    if (authStore.isAuthenticated && requiresOrganization && !authStore.contextLoaded) {
        return {
            name: 'organizations.select',

            query: {
                redirect: to.fullPath,
            },
        }
    }

    if (guestOnly && authStore.isAuthenticated) {
        if (!authStore.contextLoaded) {
            return {
                name: 'organizations.select',
            }
        }

        return {
            name: 'dashboard',
        }
    }

    return true
}
