<template>
  <AppInput
    v-bind="inputProps"
    :type="inputType"
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
      v-if="showToggle || $slots.append"
      #append
    >
      <slot name="append">
        <button
          v-if="showToggle"
          type="button"
          class="app-password__toggle"
          :aria-label="toggleLabel"
          :aria-pressed="isVisible"
          :disabled="disabled"
          @click="toggleVisibility"
        >
          {{ isVisible ? 'Ocultar' : 'Mostrar' }}
        </button>
      </slot>
    </template>
  </AppInput>
</template>

<script setup>
import { computed, ref } from 'vue'

import AppInput from '@/components/forms/fields/AppInput/index.vue'
import { appPasswordProps } from './props.js'
import { pick } from '@/components/forms/shared/utils/pick.js'
import { INPUT_CONTROL_KEYS } from '@/components/forms/shared/constants/control-keys.js'

const props = defineProps(appPasswordProps)

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

const isVisible = ref(false)

const inputType = computed(() =>
  isVisible.value ? 'text' : 'password'
)

const toggleLabel = computed(() =>
  isVisible.value
    ? props.visibleLabel
    : props.hiddenLabel
)

const inputProps = computed(() => ({
  ...pick(props, INPUT_CONTROL_KEYS),
  label: props.label,
  hint: props.hint,
  error: props.error,
}))
  
function toggleVisibility() {
  if (props.disabled) {
    return
  }

  isVisible.value = !isVisible.value
}
</script>

<style src="./style.css"></style>