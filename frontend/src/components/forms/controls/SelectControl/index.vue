<template>
  <select
    class="select-control"
    :value="modelValue"
    :id="id"
    :name="name"
    :disabled="disabled"
    :required="required"
    :autofocus="autofocus"
    :aria-invalid="ariaInvalid"
    :aria-describedby="ariaDescribedBy"
    @change="emitModelValue"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  >
    <option
      v-if="placeholder"
      value=""
      disabled
    >
      {{ placeholder }}
    </option>

    <option
      v-for="option in options"
      :key="getOptionKey(option)"
      :value="getOptionValue(option)"
    >
      {{ getOptionLabel(option) }}
    </option>
  </select>
</template>

<script setup>
import { useFieldContext } from '@/composables/useFieldContext.js'
import { selectControlProps } from './props.js'

const props = defineProps(selectControlProps)

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

const {
  ariaDescribedBy,
  ariaInvalid,
} = useFieldContext()

function getOptionLabel(option) {
  if (option !== null && typeof option === 'object') {
    return option[props.optionLabel]
  }

  return option
}

function getOptionValue(option) {
  if (option !== null && typeof option === 'object') {
    return option[props.optionValue]
  }

  return option
}

function getOptionKey(option) {
  return getOptionValue(option)
}

function emitModelValue(event) {
  emit('update:modelValue', event.target.value)
}
</script>

<style src="./style.css"></style>