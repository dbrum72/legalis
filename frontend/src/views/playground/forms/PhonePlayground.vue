<template>
  <PlaygroundSection
    title="AppPhone"
    description="Campo de telefone com semântica apropriada e ícone opcional."
  >
    <PlaygroundExample
      title="Padrão"
      description="Utiliza type, autocomplete e inputmode próprios para telefone."
      :code="defaultCode"
    >
      <AppPhone
        v-model="phone"
        id="playground-phone-default"
        name="phone"
        label="Telefone"
        placeholder="(53) 99999-9999"
      />

      <p class="playground-value">
        Valor: {{ phone || 'vazio' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Sem ícone"
      description="Mantém a semântica do telefone sem exibir o ícone."
      :code="withoutIconCode"
    >
      <AppPhone
        v-model="phoneWithoutIcon"
        id="playground-phone-without-icon"
        label="Telefone alternativo"
        :show-icon="false"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido com mensagem associada."
      :code="errorCode"
    >
      <AppPhone
        v-model="invalidPhone"
        id="playground-phone-error"
        label="Telefone"
        error="Informe um número de telefone válido."
        required
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Desabilitado"
      description="Campo indisponível para edição."
      :code="disabledCode"
    >
      <AppPhone
        v-model="disabledPhone"
        id="playground-phone-disabled"
        label="Telefone"
        disabled
      />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppPhone } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const phone = ref('')
const phoneWithoutIcon = ref('')
const invalidPhone = ref('123')
const disabledPhone = ref('(53) 99999-9999')

const defaultCode = `<AppPhone
  v-model="phone"
  id="phone"
  name="phone"
  label="Telefone"
  placeholder="(53) 99999-9999"
/>`

const withoutIconCode = `<AppPhone
  v-model="phone"
  id="phone"
  label="Telefone alternativo"
  :show-icon="false"
/>`

const errorCode = `<AppPhone
  v-model="phone"
  id="phone"
  label="Telefone"
  error="Informe um número de telefone válido."
  required
/>`

const disabledCode = `<AppPhone
  v-model="phone"
  id="phone"
  label="Telefone"
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