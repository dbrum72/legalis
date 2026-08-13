import { useAuthStore } from '@/stores/auth.js'

export function permissionGuard(to) {
    const authStore = useAuthStore()

    const permission = to.matched.map((record) => record.meta.permission).find(Boolean)

    if (!permission) {
        return true
    }

    if (authStore.hasPermission(permission)) {
        return true
    }

    return {
        name: 'dashboard',
    }
}
