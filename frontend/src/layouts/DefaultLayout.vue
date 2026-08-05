<template>
    <div class="app-layout">
        <aside class="app-sidebar">
            <SideBar />
        </aside>

        <header class="app-header">
            <HeaderBar />
        </header>

        <main class="app-main">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
import HeaderBar from '@/components/layout/HeaderBar.vue'
import SideBar from '@/components/layout/SideBar.vue'
</script>