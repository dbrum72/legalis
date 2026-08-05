<template>
  <PlaygroundSection
    title="AppInput"
    description="Campo de texto padrão do Design System."
  >
    <PlaygroundExample
      title="Normal"
      description="Estado padrão do campo."
      :code="normalCode"
    >
      <AppInput
        v-model="email"
        label="E-mail"
      >
        <template #append>
          <button
            type="button"
            class="btn btn-sm btn-outline-secondary"
          >
            Teste
          </button>
        </template>
      </AppInput>
    </PlaygroundExample>

    <PlaygroundExample
      title="Somente leitura"
      description="Exibe um valor sem permitir alterações."
      :code="readonlyCode"
    >
      <AppInput
        v-model="readonlyValue"
        id="playground-input-readonly"
        label="Número do processo"
        readonly
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido com mensagem associada ao controle."
      :code="errorCode"
    >
      <AppInput
        v-model="invalidValue"
        id="playground-input-error"
        label="E-mail"
        error="Informe um e-mail válido."
        required
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Prepend e append"
      description="Exemplo de conteúdo complementar antes e depois do campo."
      :code="slotsCode"
    >
      <AppInput
        v-model="email"
        id="playground-input-slots"
        label="E-mail"
        placeholder="usuario"
      >
        <template #prepend>
          <span>@</span>
        </template>

        <template #append>
          <span>.com.br</span>
        </template>
      </AppInput>
    </PlaygroundExample>

    <PlaygroundExample
  title="Slot append"
  description="Validação do conteúdo inserido após o input."
>
  <AppInput
    v-model="email"
    id="playground-input-button"
    label="E-mail"
  >
    <template #append>
      <button
        type="button"
        @click="buttonClicked = !buttonClicked"
      >
        Testar
      </button>
    </template>
  </AppInput>

  <p>
    Estado: {{ buttonClicked ? 'clicado' : 'não clicado' }}
  </p>
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
const email = ref('')
const buttonClicked = ref(false)

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

const slotsCode = `<AppInput
  v-model="email"
  id="email-with-slots"
  label="E-mail"
  placeholder="usuario"
>
  <template #prepend>
    <span>@</span>
  </template>

  <template #append>
    <span>.com.br</span>
  </template>
</AppInput>`
</script>