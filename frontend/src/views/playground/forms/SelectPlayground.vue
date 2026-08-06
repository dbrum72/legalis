<template>
  <PlaygroundSection
    title="AppSelect"
    description="Campo de seleção baseado em BaseField e SelectControl."
  >
    <PlaygroundExample
      title="Padrão"
      description="Select utilizando objetos label/value."
      :code="defaultCode"
    >
      <AppSelect
        v-model="selectedStatus"
        id="playground-select-default"
        label="Status"
        placeholder="Selecione..."
        :options="statusOptions"
      />

      <p class="playground-value">
        Valor: {{ selectedStatus ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Valores primitivos"
      description="Select utilizando array simples."
      :code="primitiveCode"
    >
      <AppSelect
        v-model="selectedPriority"
        id="playground-select-primitives"
        label="Prioridade"
        :options="priorities"
      />

      <p class="playground-value">
        Valor: {{ selectedPriority ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Campos personalizados"
      description="Utiliza optionLabel e optionValue."
      :code="customCode"
    >
      <AppSelect
        v-model="selectedUser"
        id="playground-select-custom"
        label="Usuário"
        :options="users"
        option-label="name"
        option-value="id"
      />

      <p class="playground-value">
        Valor: {{ selectedUser ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Obrigatório"
      description="Campo obrigatório."
      :code="requiredCode"
    >
      <AppSelect
        v-model="requiredValue"
        id="playground-select-required"
        label="Categoria"
        required
        :options="statusOptions"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido."
      :code="errorCode"
    >
      <AppSelect
        v-model="errorValue"
        id="playground-select-error"
        label="Categoria"
        error="Selecione uma opção."
        :options="statusOptions"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Desabilitado"
      description="Campo desabilitado."
      :code="disabledCode"
    >
      <AppSelect
        v-model="disabledValue"
        id="playground-select-disabled"
        label="Categoria"
        disabled
        :options="statusOptions"
      />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppSelect } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const statusOptions = [
  { label: 'Ativo', value: 1 },
  { label: 'Inativo', value: 2 },
  { label: 'Suspenso', value: 3 },
]

const priorities = [
  'Baixa',
  'Média',
  'Alta',
]

const users = [
  { id: 10, name: 'Administrador' },
  { id: 20, name: 'Operador' },
  { id: 30, name: 'Convidado' },
]

const selectedStatus = ref(null)
const selectedPriority = ref('Média')
const selectedUser = ref(20)
const requiredValue = ref(null)
const errorValue = ref(null)
const disabledValue = ref(2)

const defaultCode = `<AppSelect
  v-model="status"
  :options="statusOptions"
/>`

const primitiveCode = `<AppSelect
  v-model="priority"
  :options="['Baixa','Média','Alta']"
/>`

const customCode = `<AppSelect
  v-model="user"
  :options="users"
  option-label="name"
  option-value="id"
/>`

const requiredCode = `<AppSelect
  required
/>`

const errorCode = `<AppSelect
  error="Selecione uma opção."
/>`

const disabledCode = `<AppSelect
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