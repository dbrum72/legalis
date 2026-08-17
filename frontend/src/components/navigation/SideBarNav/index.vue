<template>
    <nav class="sidebar-nav" aria-label="Navegação principal">
        <SideBarItem v-for="item in visibleMenuItems" :key="item.id" :item="item" />
    </nav>
</template>

<script setup>
import {
    computed,
} from 'vue'

import SideBarItem from '@/components/navigation/SideBarItem/index.vue'

import menuItems from '@/config/menu.js'

import { useAuthStore } from '@/stores/auth.js'

const authStore =
    useAuthStore()

const visibleMenuItems =
    computed(() =>
        menuItems.filter(
            (item) => {
                if (!item.permission) {
                    return true
                }

                return authStore.hasPermission(
                    item.permission,
                )
            },
        ),
    )
</script>