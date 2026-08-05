<template>
  <BaseField
    :id="id"
    :label="label"
    :hint="hint"
    :error="error"
    :required="required"
    :disabled="disabled"
    :readonly="readonly"
  >
    <SelectControl
      v-bind="controlProps"
      @update:model-value="emit('update:modelValue', $event)"
      @focus="emit('focus', $event)"
      @blur="emit('blur', $event)"
    />
  </BaseField>
</template>

<script setup>
import { computed } from 'vue'

import BaseField from '@/components/forms/fields/BaseField/index.vue'
import SelectControl from '@/components/forms/controls/SelectControl/index.vue'
import { appSelectProps } from './props.js'

const props = defineProps(appSelectProps)

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

const controlProps = computed(() => ({
  modelValue: props.modelValue,
  id: props.id,
  name: props.name,
  placeholder: props.placeholder,
  disabled: props.disabled,
  readonly: props.readonly,
  required: props.required,
  autofocus: props.autofocus,
  options: props.options,
  optionLabel: props.optionLabel,
  optionValue: props.optionValue,
}))
</script>