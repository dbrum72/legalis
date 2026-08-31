<template>
    <aside class="app-sidebar app-sidebar-nav" aria-label="Barra lateral">
        <header class="sidebar-header">
            <div class="sidebar-brand">
                <img :src="legalisSidebarLogo" class="sidebar-brand__symbol" width="42" height="44" alt=""
                    aria-hidden="true" decoding="async" draggable="false">

                <div class="sidebar-brand__text">
                    <span class="sidebar-brand__name">Legalis</span>
                    <span class="sidebar-brand__tagline">Escritório Jurídico</span>
                </div>
            </div>
        </header>

        <SideBarNav />

        <footer class="sidebar-footer"></footer>
    </aside>
</template>

<script setup>
import legalisSidebarLogo from '@/assets/brand/legalis-sidebar-logo.png'
import SideBarNav from '@/components/navigation/SideBarNav/index.vue'
</script>

<style scoped>
.app-sidebar-nav {
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow: hidden;
}

.sidebar-header {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
    min-height: var(--header-height);
    padding: 0 1.5rem;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    min-width: 0;
}

.sidebar-brand__symbol {
    display: block;
    flex: 0 0 auto;
    width: 42px;
    height: 44px;
    object-fit: contain;
    user-select: none;
}

.sidebar-brand__text {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}

.sidebar-brand__name,
.sidebar-brand__tagline {
    display: block;
    white-space: nowrap;
}

.sidebar-brand__name {
    color: var(--sidebar-brand-title-color, #f6f2e9);
    font-family: var(--font-family-heading, inherit);
    font-size: 1.125rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0.035em;
    text-transform: uppercase;
}

.sidebar-brand__tagline {
    margin-top: 0.25rem;
    color: var(--sidebar-brand-subtitle-color, #d8c79f);
    font-size: 0.5rem;
    font-weight: 500;
    line-height: 1;
    letter-spacing: 0.055em;
    text-transform: uppercase;
}

.app-sidebar-nav :deep(.sidebar-nav) {
    flex: 1 1 auto;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
}

.sidebar-footer {
    flex: 0 0 auto;
}
</style>
