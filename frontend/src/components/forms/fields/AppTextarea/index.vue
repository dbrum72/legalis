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
    <TextareaControl
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
import TextareaControl from '@/components/forms/controls/TextareaControl/index.vue'
import { appTextareaProps } from './props.js'
import { pick } from '@/components/forms/shared/utils/pick.js'
import { SELECT_CONTROL_KEYS } from '@/components/forms/shared/constants/control-keys.js'


const props = defineProps(appTextareaProps)

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

const controlProps = computed(() =>
  pick(props, SELECT_CONTROL_KEYS)
)
</script>