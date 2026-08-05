<template>
  <PlaygroundSection title="AppInput" description="Campo de texto padrão do Design System.">
    <PlaygroundExample title="Normal" description="Estado padrão do campo." :code="normalCode">
      <AppInput v-model="normal" id="playground-input-normal" label="Nome" placeholder="Digite seu nome" />
    </PlaygroundExample>

    <PlaygroundExample title="Somente leitura" description="Exibe um valor sem permitir alterações."
      :code="readonlyCode">
      <AppInput v-model="readonlyValue" id="playground-input-readonly" label="Número do processo" readonly />
    </PlaygroundExample>

    <PlaygroundExample title="Erro" description="Estado inválido com mensagem associada ao controle." :code="errorCode">
      <AppInput v-model="invalidValue" id="playground-input-error" label="E-mail" error="Informe um e-mail válido."
        required />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppInput } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const normal = ref('')
const readonlyValue = ref('5001234-56.2026.8.21.0001')
const invalidValue = ref('email-invalido')

const normalCode = `<AppInput
  v-model="name"
  id="name"
  label="Nome"
  placeholder="Digite seu nome"
/>`

const readonlyCode = `<AppInput
  v-model="processNumber"
  id="process-number"
  label="Número do processo"
  readonly
/>`

const errorCode = `<AppInput
  v-model="email"
  id="email"
  label="E-mail"
  error="Informe um e-mail válido."
  required
/>`
</script>