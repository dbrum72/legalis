<template>
  <PlaygroundSection
    title="AppEmail"
    description="Campo de e-mail com configurações semânticas e ícone opcional."
  >
    <PlaygroundExample
      title="Padrão"
      description="Utiliza type, autocomplete e inputmode próprios para e-mail."
      :code="defaultCode"
    >
      <AppEmail
        v-model="email"
        id="playground-email-default"
        name="email"
        label="E-mail"
        placeholder="usuario@exemplo.com"
      />

      <p class="playground-value">
        Valor: {{ email || 'vazio' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Sem ícone"
      description="Mantém o comportamento semântico, mas remove o ícone."
      :code="withoutIconCode"
    >
      <AppEmail
        v-model="emailWithoutIcon"
        id="playground-email-without-icon"
        label="E-mail alternativo"
        :show-icon="false"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido com mensagem associada."
      :code="errorCode"
    >
      <AppEmail
        v-model="invalidEmail"
        id="playground-email-error"
        label="E-mail"
        error="Informe um endereço de e-mail válido."
        required
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Desabilitado"
      description="Campo indisponível para edição."
      :code="disabledCode"
    >
      <AppEmail
        v-model="disabledEmail"
        id="playground-email-disabled"
        label="E-mail"
        disabled
      />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppEmail } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const email = ref('')
const emailWithoutIcon = ref('')
const invalidEmail = ref('email-invalido')
const disabledEmail = ref('usuario@exemplo.com')

const defaultCode = `<AppEmail
  v-model="email"
  id="email"
  name="email"
  label="E-mail"
  placeholder="usuario@exemplo.com"
/>`

const withoutIconCode = `<AppEmail
  v-model="email"
  id="email"
  label="E-mail alternativo"
  :show-icon="false"
/>`

const errorCode = `<AppEmail
  v-model="email"
  id="email"
  label="E-mail"
  error="Informe um endereço de e-mail válido."
  required
/>`

const disabledCode = `<AppEmail
  v-model="email"
  id="email"
  label="E-mail"
  disabled
/>`
</script>

<style scoped>
.playground-value {
  margin: 0;

  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}
</style>