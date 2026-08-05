<template>
  <AppInput
    v-bind="inputProps"
    type="number"
    inputmode="decimal"
    :min="min"
    :max="max"
    :step="step"
    @update:model-value="updateValue"
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
  </AppInput>
</template>

<script setup>
import { computed } from 'vue'

import AppInput from '@/components/forms/fields/AppInput/index.vue'
import { appNumberProps } from './props.js'

const props = defineProps(appNumberProps)

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

const inputProps = computed(() => {
  const {
    modelValue,
    type,
    inputmode,
    min,
    max,
    step,
    allowEmpty,
    showIcon,
    ...forwardedProps
  } = props

  return {
    ...forwardedProps,
    modelValue,
  }
})

function updateValue(value) {
  if (value === '' || value === null || value === undefined) {
    emit(
      'update:modelValue',
      props.allowEmpty ? null : 0,
    )

    return
  }

  const numericValue = Number(value)

  if (!Number.isFinite(numericValue)) {
    return
  }

  emit('update:modelValue', numericValue)
}
</script>

<style src="./style.css"></style>