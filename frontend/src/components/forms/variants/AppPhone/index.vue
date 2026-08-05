<template>
  <AppInput
    v-bind="inputProps"
    type="tel"
    autocomplete="tel"
    inputmode="tel"
    @update:model-value="emit('update:modelValue', $event)"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  >
    <template
      v-if="showIcon || $slots.prepend"
      #prepend
    >
      <slot name="prepend">
        <InputIcon v-if="showIcon">
          <AppIcon
            name="phone"
            :size="18"
          />
        </InputIcon>
      </slot>
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
import { InputIcon } from '@/components/forms/internal/index.js'
import AppIcon from '@/components/ui/AppIcon.vue'
import { appPhoneProps } from './props.js'

const props = defineProps(appPhoneProps)

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

const inputProps = computed(() => {
  const {
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