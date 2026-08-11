<template>
  <div class="app-layout">
    <SideBar />

    <HeaderBar />

    <main class="app-main">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'

import {
  HeaderBar,
  SideBar,
} from '@/components/layout'
</script>