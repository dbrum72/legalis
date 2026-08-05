<template>
  <AppField
    :id="id"
    :label="label"
    :hint="hint"
    :error="error"
    :required="required"
    :disabled="disabled"
    :readonly="readonly"
  >
    <slot />
  </AppField>
</template>

<script setup>
import AppField from '@/components/forms/fields/AppField/index.vue'
import { fieldProps } from '@/components/forms/shared/props/field.js'

defineProps(fieldProps)
</script>