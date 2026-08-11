<template>
    <aside class="app-sidebar app-sidebar-nav">

        <header class="sidebar-header">
            <AppLogo />
        </header>

        <SideBarNav />

        <footer class="sidebar-footer">
        </footer>

    </aside>
</template>

<script setup>
import AppLogo from '@/components/ui/AppLogo/index.vue'
import SideBarNav from '@/components/navigation/SideBarNav/index.vue'
</script>