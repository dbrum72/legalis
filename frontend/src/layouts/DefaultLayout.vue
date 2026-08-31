<template>
  <div class="app-layout" :class="{ 'app-layout--sidebar-closed': !sidebarOpen }">
    <SideBar />

    <HeaderBar :sidebar-open="sidebarOpen" @toggle-sidebar="sidebarOpen = !sidebarOpen" />

    <main class="app-main">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterView } from 'vue-router'

import {
  HeaderBar,
  SideBar,
} from '@/components/layout'

const sidebarOpen = ref(true)
</script>
