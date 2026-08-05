<template>
    <nav class="sidebar-nav" aria-label="Navegação principal">
        <SideBarItem v-for="item in menuItems" :key="item.id" :item="item" />
    </nav>
</template>

<script setup>
import SideBarItem from '@/components/navigation/SideBarItem.vue'
import menuItems from '@/config/menu.js'
</script>