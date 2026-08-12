<template>
    <header class="app-header app-header-bar">
        <div class="app-header-bar__start">
            <AppBreadcrumb />
        </div>

        <div class="app-header-bar__end">
            <div v-if="authStore.user" class="app-header-bar__user">
                <span class="app-header-bar__user-name">
                    {{ authStore.userName }}
                </span>

                <AppButton variant="ghost" size="sm" :loading="loggingOut" :disabled="loggingOut"
                    aria-label="Sair da aplicação" @click="handleLogout">
                    Sair
                </AppButton>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { AppBreadcrumb } from '@/components/navigation'
import { AppButton } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'

const router = useRouter()
const authStore = useAuthStore()

const loggingOut = ref(false)

async function handleLogout() {
    if (loggingOut.value) {
        return
    }

    loggingOut.value = true

    try {
        await authStore.logout()
    } finally {
        loggingOut.value = false

        await router.replace({
            name: 'login',
        })
    }
}
</script>