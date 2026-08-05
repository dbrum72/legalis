<template>
  <component
    :is="iconComponent"
    :size="size"
    :stroke-width="strokeWidth"
    :aria-hidden="decorative"
    :aria-label="decorative ? undefined : label"
  />
</template>

<script setup>
import { computed } from 'vue'

import { icons } from '@/icons/index.js'

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

const iconComponent = computed(() =>
  icons[props.name] ?? icons.file
)
</script>