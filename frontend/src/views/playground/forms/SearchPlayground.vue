<template>
    <PlaygroundSection title="AppSearch" description="Campo de pesquisa com ícone e ação para limpar.">
        <PlaygroundExample title="Padrão" description="O botão de limpeza aparece quando existe conteúdo."
            :code="defaultCode">
            <AppSearch v-model="search" id="playground-search-default" name="search" label="Pesquisar"
                placeholder="Digite um termo" autocomplete="off" />

            <p class="playground-value">
                Valor: {{ search || 'vazio' }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Sem limpeza"
            description="Mantém o ícone de pesquisa, mas não exibe o botão de limpar." :code="notClearableCode">
            <AppSearch v-model="fixedSearch" id="playground-search-not-clearable" label="Pesquisar processos"
                :clearable="false" />
        </PlaygroundExample>

        <PlaygroundExample title="Somente leitura" description="Não permite digitação nem limpeza."
            :code="readonlyCode">
            <AppSearch v-model="readonlySearch" id="playground-search-readonly" label="Filtro aplicado" readonly />
        </PlaygroundExample>

        <PlaygroundExample title="Desabilitado" description="Campo e ações ficam indisponíveis." :code="disabledCode">
            <AppSearch v-model="disabledSearch" id="playground-search-disabled" label="Pesquisar" disabled />
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppSearch } from '@/components/forms'
import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const search = ref('')
const fixedSearch = ref('contrato')
const readonlySearch = ref('processo civil')
const disabledSearch = ref('indisponível')

const defaultCode = `<AppSearch
  v-model="search"
  id="search"
  label="Pesquisar"
  placeholder="Digite um termo"
/>`

const notClearableCode = `<AppSearch
  v-model="search"
  id="search"
  label="Pesquisar processos"
  :clearable="false"
/>`

const readonlyCode = `<AppSearch
  v-model="search"
  id="search"
  label="Filtro aplicado"
  readonly
/>`

const disabledCode = `<AppSearch
  v-model="search"
  id="search"
  label="Pesquisar"
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