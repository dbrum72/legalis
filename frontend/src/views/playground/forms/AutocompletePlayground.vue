<template>
  <PlaygroundSection
    title="AppAutocomplete"
    description="Campo autocomplete baseado em BaseField e AutocompleteControl."
  >
    <PlaygroundExample
      title="Padrão"
      description="Pesquisa utilizando objetos label/value."
      :code="defaultCode"
    >
      <AppAutocomplete
        v-model="selectedUser"
        v-model:searchValue="search"
        id="playground-autocomplete-default"
        label="Usuário"
        placeholder="Digite para pesquisar..."
        :options="users"
      />

      <p class="playground-value">
        modelValue: {{ selectedUser ?? 'null' }}
      </p>

      <p class="playground-value">
        searchValue: "{{ search }}"
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Pesquisa mínima"
      description="Lista aberta somente após dois caracteres."
      :code="minimumCode"
    >
      <AppAutocomplete
        v-model="minimumUser"
        v-model:searchValue="minimumSearch"
        id="playground-autocomplete-minimum"
        label="Usuário"
        :options="users"
        :min-search-length="2"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Campos personalizados"
      description="Utilizando optionLabel e optionValue."
      :code="customCode"
    >
      <AppAutocomplete
        v-model="selectedEmployee"
        v-model:searchValue="employeeSearch"
        id="playground-autocomplete-custom"
        label="Funcionário"
        :options="employees"
        option-label="name"
        option-value="id"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Obrigatório"
      description="Campo obrigatório."
      :code="requiredCode"
    >
      <AppAutocomplete
        v-model="requiredValue"
        v-model:searchValue="requiredSearch"
        id="playground-autocomplete-required"
        label="Usuário"
        required
        :options="users"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido."
      :code="errorCode"
    >
      <AppAutocomplete
        v-model="errorValue"
        v-model:searchValue="errorSearch"
        id="playground-autocomplete-error"
        label="Usuário"
        error="Selecione um usuário."
        :options="users"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Desabilitado"
      description="Autocomplete desabilitado."
      :code="disabledCode"
    >
      <AppAutocomplete
        v-model="disabledValue"
        v-model:searchValue="disabledSearch"
        id="playground-autocomplete-disabled"
        label="Usuário"
        disabled
        :options="users"
      />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppAutocomplete } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const users = [
  { label: 'Administrador', value: 10 },
  { label: 'Operador', value: 20 },
  { label: 'Convidado', value: 30 },
]

const employees = [
  { id: 100, name: 'Ana Souza' },
  { id: 200, name: 'Carlos Lima' },
  { id: 300, name: 'Marina Rocha' },
]

const selectedUser = ref(null)
const search = ref('')

const minimumUser = ref(null)
const minimumSearch = ref('')

const selectedEmployee = ref(null)
const employeeSearch = ref('')

const requiredValue = ref(null)
const requiredSearch = ref('')

const errorValue = ref(null)
const errorSearch = ref('')

const disabledValue = ref(20)
const disabledSearch = ref('Operador')

const defaultCode = `<AppAutocomplete
  v-model="user"
  v-model:searchValue="search"
  :options="users"
/>`

const minimumCode = `<AppAutocomplete
  :min-search-length="2"
/>`

const customCode = `<AppAutocomplete
  option-label="name"
  option-value="id"
/>`

const requiredCode = `<AppAutocomplete required />`

const errorCode = `<AppAutocomplete error="Selecione um usuário." />`

const disabledCode = `<AppAutocomplete disabled />`
</script>

<style scoped>
.playground-value {
  margin-top: var(--space-2);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
}
</style>