<template>
  <PlaygroundSection
    title="AppTextarea"
    description="Campo multilinha baseado em AppField e TextareaControl."
  >
    <PlaygroundExample
      title="Padrão"
      description="Textarea com configuração padrão."
      :code="defaultCode"
    >
      <AppTextarea
        v-model="defaultValue"
        id="playground-textarea-default"
        name="description"
        label="Descrição"
        placeholder="Digite uma descrição..."
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Com limite"
      description="Controle utilizando maxlength."
      :code="maxlengthCode"
    >
      <AppTextarea
        v-model="limitedValue"
        id="playground-textarea-limit"
        label="Observações"
        :maxlength="120"
      />

      <p class="playground-value">
        {{ limitedValue.length }}/120
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Obrigatório"
      description="Campo obrigatório."
      :code="requiredCode"
    >
      <AppTextarea
        v-model="requiredValue"
        id="playground-textarea-required"
        label="Justificativa"
        required
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido."
      :code="errorCode"
    >
      <AppTextarea
        v-model="errorValue"
        id="playground-textarea-error"
        label="Comentário"
        error="Campo obrigatório."
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Somente leitura"
      description="Textarea readonly."
      :code="readonlyCode"
    >
      <AppTextarea
        v-model="readonlyValue"
        id="playground-textarea-readonly"
        label="Texto"
        readonly
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Desabilitado"
      description="Textarea desabilitado."
      :code="disabledCode"
    >
      <AppTextarea
        v-model="disabledValue"
        id="playground-textarea-disabled"
        label="Texto"
        disabled
      />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppTextarea } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const defaultValue = ref('')
const limitedValue = ref('')
const requiredValue = ref('')
const errorValue = ref('')
const readonlyValue = ref(
  'Este texto está disponível apenas para leitura.',
)
const disabledValue = ref(
  'Textarea desabilitado.',
)

const defaultCode = `<AppTextarea
  v-model="description"
  id="description"
  label="Descrição"
/>`

const maxlengthCode = `<AppTextarea
  v-model="notes"
  :maxlength="120"
/>`

const requiredCode = `<AppTextarea
  v-model="reason"
  required
/>`

const errorCode = `<AppTextarea
  v-model="comment"
  error="Campo obrigatório."
/>`

const readonlyCode = `<AppTextarea
  v-model="text"
  readonly
/>`

const disabledCode = `<AppTextarea
  v-model="text"
  disabled
/>`
</script>

<style scoped>
.playground-value {
  margin-top: var(--space-2);

  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}
</style>