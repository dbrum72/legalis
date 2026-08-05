<template>
    <RouterLink class="app-logo" :to="{ name: 'dashboard' }">
        <span class="app-logo__text">
            Legalis
        </span>
    </RouterLink>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>