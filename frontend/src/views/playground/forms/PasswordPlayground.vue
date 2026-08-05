<template>
  <PlaygroundSection
    title="AppPassword"
    description="Campo de senha com controle de visibilidade."
  >
    <PlaygroundExample
      title="Padrão"
      description="Permite mostrar ou ocultar o conteúdo digitado."
      :code="defaultCode"
    >
      <AppPassword
        v-model="password"
        id="playground-password-default"
        name="password"
        label="Senha"
        placeholder="Digite sua senha"
        autocomplete="current-password"
      />

      <p class="playground-value">
        Valor: {{ password || 'vazio' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Sem alternador"
      description="Campo de senha sem o botão de visibilidade."
      :code="withoutToggleCode"
    >
      <AppPassword
        v-model="passwordWithoutToggle"
        id="playground-password-no-toggle"
        name="password-no-toggle"
        label="Senha"
        placeholder="Digite sua senha"
        :show-toggle="false"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Desabilitado"
      description="O campo e o botão de alternância ficam indisponíveis."
      :code="disabledCode"
    >
      <AppPassword
        v-model="disabledPassword"
        id="playground-password-disabled"
        name="password-disabled"
        label="Senha"
        disabled
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido com mensagem associada."
      :code="errorCode"
    >
      <AppPassword
        v-model="invalidPassword"
        id="playground-password-error"
        name="password-error"
        label="Senha"
        error="A senha deve possuir pelo menos oito caracteres."
        required
      />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppPassword } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const password = ref('')
const passwordWithoutToggle = ref('')
const disabledPassword = ref('senha-protegida')
const invalidPassword = ref('123')

const defaultCode = `<AppPassword
  v-model="password"
  id="password"
  name="password"
  label="Senha"
  placeholder="Digite sua senha"
  autocomplete="current-password"
/>`

const withoutToggleCode = `<AppPassword
  v-model="password"
  id="password"
  label="Senha"
  :show-toggle="false"
/>`

const disabledCode = `<AppPassword
  v-model="password"
  id="password"
  label="Senha"
  disabled
/>`

const errorCode = `<AppPassword
  v-model="password"
  id="password"
  label="Senha"
  error="A senha deve possuir pelo menos oito caracteres."
  required
/>`
</script>

<style scoped>
.playground-value {
  margin: 0;

  color: var(--color-text-muted);

  font-size: var(--font-size-sm);
}
</style>