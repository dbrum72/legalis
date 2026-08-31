<template>
    <header class="app-header app-header-bar">
        <button class="app-header-bar__menu" type="button" aria-label="Alternar menu lateral"
            :aria-expanded="sidebarOpen" @click="$emit('toggle-sidebar')">
            <Menu :size="21" :stroke-width="1.8" aria-hidden="true" />
        </button>

        <div v-if="authStore.user" class="app-header-bar__welcome">
            <strong>{{ greeting }}, {{ displayName }} <span aria-hidden="true">👋</span></strong>
            <span>Último acesso: {{ lastAccess }}</span>
        </div>

        <nav class="app-header-bar__actions" aria-label="Ações do usuário">
            <button class="app-header-bar__icon-button app-header-bar__notifications" type="button"
                aria-label="Notificações, 3 não lidas">
                <Bell :size="21" :stroke-width="1.8" aria-hidden="true" />
                <span class="app-header-bar__badge">3</span>
            </button>
            <button class="app-header-bar__icon-button" type="button" aria-label="Configurações">
                <Settings :size="20" :stroke-width="1.8" aria-hidden="true" />
            </button>
            <div v-if="authStore.user" class="app-header-bar__profile">
                <span class="app-header-bar__avatar" aria-hidden="true">{{ initials }}</span>
                <span class="app-header-bar__identity">
                    <strong>{{ authStore.userName }}</strong>
                    <span>{{ roleLabel }}</span>
                </span>
                <button class="app-header-bar__profile-menu" type="button" aria-label="Sair da aplicação"
                    :disabled="loggingOut" @click="handleLogout">
                    <ChevronDown :size="16" :stroke-width="1.8" aria-hidden="true" />
                    <span class="app-header-bar__sr-only">Sair</span>
                </button>
            </div>
        </nav>
    </header>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Bell, ChevronDown, Menu, Settings } from '@lucide/vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

defineProps({ sidebarOpen: { type: Boolean, default: true } })
defineEmits(['toggle-sidebar'])

const router = useRouter()
const authStore = useAuthStore()
const loggingOut = ref(false)
const currentHour = new Date().getHours()
const greeting = currentHour < 12 ? 'Bom dia' : currentHour < 18 ? 'Boa tarde' : 'Boa noite'
const displayName = computed(() => authStore.userName || 'Usuário')
const initials = computed(() => displayName.value.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase())
const roleLabel = computed(() => {
    const role = authStore.roles[0]
    return role ? role.split('-').map((part) => `${part.charAt(0).toUpperCase()}${part.slice(1)}`).join(' ') : 'Administrador'
})
const lastAccess = computed(() => authStore.user?.last_login_at
    ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(authStore.user.last_login_at))
    : 'Hoje')

async function handleLogout() {
    if (loggingOut.value) return
    loggingOut.value = true
    try { await authStore.logout() } finally {
        loggingOut.value = false
        await router.replace({ name: 'login' })
    }
}
</script>
