<template>
  <InputVariant
    :model-value="modelValue"
    :input-props="inputProps"
    type="tel"
    autocomplete="tel"
    inputmode="tel"
    icon="phone"
    :show-icon="showIcon"
    @update:model-value="emit('update:modelValue', $event)"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  >
    <template
      v-if="$slots.prepend"
      #prepend
    >
      <slot name="prepend" />
    </template>

    <template
      v-if="$slots.append"
      #append
    >
      <slot name="append" />
    </template>
  </InputVariant>
</template>

<script setup>
import { computed } from 'vue'

import InputVariant from '@/components/forms/internal/InputVariant.vue'
import { appPhoneProps } from './props.js'

const props = defineProps(appPhoneProps)

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

const inputProps = computed(() => {
  const {
    modelValue,
    showIcon,
    type,
    autocomplete,
    inputmode,
    ...forwardedProps
  } = props

  return forwardedProps
})
</script>

<style src="./style.css"></style>