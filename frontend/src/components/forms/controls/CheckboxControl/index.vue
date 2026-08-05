<template>
  <input
    class="checkbox-control"
    type="checkbox"
    :id="id"
    :name="name"
    :checked="modelValue"
    :disabled="disabled"
    :required="required"
    :autofocus="autofocus"
    :aria-invalid="ariaInvalid"
    :aria-describedby="ariaDescribedBy"
    @change="emitModelValue"
    @focus="emit('focus', $event)"
    @blur="emit('blur', $event)"
  />
</template>

<script setup>
import { checkboxControlProps } from './props.js'

defineProps(checkboxControlProps)

const emit = defineEmits([
  'update:modelValue',
  'focus',
  'blur',
])

function emitModelValue(event) {
  emit('update:modelValue', event.target.checked)
}
</script>

<style src="./style.css"></style>