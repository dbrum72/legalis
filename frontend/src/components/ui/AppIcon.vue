<template>
    <component :is="iconComponent" :size="size" :stroke-width="strokeWidth" :aria-hidden="decorative"
        :aria-label="decorative ? undefined : label" />
</template>

<script setup>
import { computed } from 'vue'
import {
    LayoutDashboard,
    Users,
    Scale,
    CalendarDays,
    Wallet,
    FileText,
    Settings,
} from '@lucide/vue'

const props = defineProps({
    name: {
        type: String,
        required: true,
    },

    size: {
        type: [Number, String],
        default: 20,
    },

    strokeWidth: {
        type: [Number, String],
        default: 2,
    },

    decorative: {
        type: Boolean,
        default: true,
    },

    label: {
        type: String,
        default: undefined,
    },
})

const icons = {
    dashboard: LayoutDashboard,
    users: Users,
    scale: Scale,
    calendar: CalendarDays,
    wallet: Wallet,
    file: FileText,
    settings: Settings,
}

const iconComponent = computed(() => {
    return icons[props.name] ?? FileText
})
</script>