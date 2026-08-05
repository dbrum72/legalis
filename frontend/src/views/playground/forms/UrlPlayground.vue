<template>
    <PlaygroundSection title="AppURL" description="Campo para endereços web com semântica e ícone próprios.">
        <PlaygroundExample title="Padrão" description="Utiliza type, autocomplete e inputmode apropriados para URL."
            :code="defaultCode">
            <AppURL v-model="url" id="playground-url-default" name="url" label="Endereço do site"
                placeholder="https://exemplo.com" />

            <p class="playground-value">
                Valor: {{ url || 'vazio' }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Sem ícone" description="Mantém o comportamento semântico sem exibir o ícone."
            :code="withoutIconCode">
            <AppURL v-model="urlWithoutIcon" id="playground-url-without-icon" label="Site alternativo"
                :show-icon="false" />
        </PlaygroundExample>

        <PlaygroundExample title="Erro" description="Estado inválido com mensagem associada." :code="errorCode">
            <AppURL v-model="invalidUrl" id="playground-url-error" label="Endereço do site"
                error="Informe uma URL válida." required />
        </PlaygroundExample>

        <PlaygroundExample title="Desabilitado" description="Campo indisponível para edição." :code="disabledCode">
            <AppURL v-model="disabledUrl" id="playground-url-disabled" label="Endereço do site" disabled />
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppURL } from '@/components/forms'
import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const url = ref('')
const urlWithoutIcon = ref('')
const invalidUrl = ref('exemplo')
const disabledUrl = ref('https://exemplo.com')

const defaultCode = `<AppURL
  v-model="url"
  id="url"
  name="url"
  label="Endereço do site"
  placeholder="https://exemplo.com"
/>`

const withoutIconCode = `<AppURL
  v-model="url"
  id="url"
  label="Site alternativo"
  :show-icon="false"
/>`

const errorCode = `<AppURL
  v-model="url"
  id="url"
  label="Endereço do site"
  error="Informe uma URL válida."
  required
/>`

const disabledCode = `<AppURL
  v-model="url"
  id="url"
  label="Endereço do site"
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