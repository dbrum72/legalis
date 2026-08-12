import { useAuthStore } from '@/stores/auth.js'

export async function authGuard(to) {
    const authStore = useAuthStore()

    if (!authStore.hydrated) {
        await authStore.hydrate()
    }

    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth)

    const guestOnly = to.matched.some((record) => record.meta.guestOnly)

    if (requiresAuth && !authStore.isAuthenticated) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        }
    }

    if (guestOnly && authStore.isAuthenticated) {
        return {
            name: 'dashboard',
        }
    }

    return true
}
