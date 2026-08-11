<template>
    <RouterLink class="sidebar-item" :to="{ name: item.name }">
        <AppIcon v-if="item.icon" class="sidebar-item__icon" :name="item.icon" :size="20" />

        <span class="sidebar-item__label">
            {{ item.label }}
        </span>
    </RouterLink>
</template>

<script setup>
import { RouterLink } from 'vue-router'

import AppIcon from '@/components/ui/AppIcon/index.vue'
import { sideBarItemProps } from './props.js'

defineProps(sideBarItemProps)
</script>