<template>
  <AppInput
    v-bind="inputProps"
    :type="type"
    :autocomplete="autocomplete"
    :inputmode="inputmode"
    @update:model-value="emit('update:modelValue', $event)"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  >
    <template
      v-if="showIcon || $slots.prepend"
      #prepend
    >
      <slot name="prepend">
        <InputIcon v-if="showIcon && icon">
          <AppIcon
            :name="icon"
            :size="iconSize"
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
import InputIcon from '@/components/forms/internal/InputIcon/index.vue'
import AppIcon from '@/components/ui/AppIcon.vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },

  type: {
    type: String,
    default: 'text',
  },

  autocomplete: {
    type: String,
    default: undefined,
  },

  inputmode: {
    type: String,
    default: undefined,
  },

  icon: {
    type: String,
    default: '',
  },

  iconSize: {
    type: [Number, String],
    default: 18,
  },

  showIcon: {
    type: Boolean,
    default: true,
  },

  inputProps: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

const inputProps = computed(() => ({
  ...props.inputProps,
  modelValue: props.modelValue,
}))
</script>