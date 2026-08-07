<template>
    <PlaygroundSection title="RadioGroup">
        <PlaygroundExample title="Básico" :code="basicCode">
            <RadioGroup v-model="gender" id="gender" name="gender" label="Sexo" :options="genderOptions" />

            <p class="mt-3">
                Selecionado: {{ gender }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Orientação horizontal" :code="horizontalCode">
            <RadioGroup v-model="priority" id="priority" name="priority" label="Prioridade" orientation="horizontal"
                :options="priorityOptions" />
        </PlaygroundExample>

        <PlaygroundExample title="Valores numéricos" :code="numericCode">
            <RadioGroup v-model="level" id="level" name="level" label="Nível" :options="levelOptions" />

            <p class="mt-3">
                Valor: {{ level }}
                — tipo: {{ typeof level }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Valores booleanos" :code="booleanCode">
            <RadioGroup v-model="active" id="active" name="active" label="Situação" :options="booleanOptions" />

            <p class="mt-3">
                Valor: {{ active }}
                — tipo: {{ typeof active }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Opção desabilitada" :code="optionDisabledCode">
            <RadioGroup v-model="access" id="access" name="access" label="Perfil de acesso" :options="accessOptions" />
        </PlaygroundExample>

        <PlaygroundExample title="Grupo desabilitado" :code="disabledCode">
            <RadioGroup v-model="disabledValue" id="disabled-group" name="disabled-group" label="Grupo desabilitado"
                :options="simpleOptions" disabled />
        </PlaygroundExample>

        <PlaygroundExample title="Obrigatório" :code="requiredCode">
            <RadioGroup v-model="requiredValue" id="required-group" name="required-group" label="Tipo"
                :options="simpleOptions" required />
        </PlaygroundExample>

        <PlaygroundExample title="Hint" :code="hintCode">
            <RadioGroup v-model="hintValue" id="hint-group" name="hint-group" label="Categoria"
                hint="Selecione a categoria aplicável." :options="simpleOptions" />
        </PlaygroundExample>

        <PlaygroundExample title="Erro" :code="errorCode">
            <RadioGroup v-model="errorValue" id="error-group" name="error-group" label="Categoria"
                hint="Este hint será ocultado enquanto houver erro." error="Selecione uma opção."
                :options="simpleOptions" required />
        </PlaygroundExample>

        <PlaygroundExample title="Propriedades personalizadas" :code="customPropertiesCode">
            <RadioGroup v-model="role" id="role" name="role" label="Perfil" option-label="name" option-value="id"
                option-disabled="blocked" :options="roleOptions" />

            <p class="mt-3">
                ID selecionado: {{ role }}
            </p>
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { RadioGroup } from '@/components/forms'

import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const gender = ref(null)
const priority = ref('medium')
const level = ref(2)
const active = ref(true)
const access = ref('user')
const disabledValue = ref('A')
const requiredValue = ref(null)
const hintValue = ref(null)
const errorValue = ref(null)
const role = ref(null)

const genderOptions = [
    {
        label: 'Masculino',
        value: 'M',
    },
    {
        label: 'Feminino',
        value: 'F',
    },
]

const priorityOptions = [
    {
        label: 'Baixa',
        value: 'low',
    },
    {
        label: 'Média',
        value: 'medium',
    },
    {
        label: 'Alta',
        value: 'high',
    },
]

const levelOptions = [
    {
        label: 'Nível 1',
        value: 1,
    },
    {
        label: 'Nível 2',
        value: 2,
    },
    {
        label: 'Nível 3',
        value: 3,
    },
]

const booleanOptions = [
    {
        label: 'Ativo',
        value: true,
    },
    {
        label: 'Inativo',
        value: false,
    },
]

const accessOptions = [
    {
        label: 'Usuário',
        value: 'user',
    },
    {
        label: 'Administrador',
        value: 'admin',
    },
    {
        label: 'Super administrador',
        value: 'super-admin',
        disabled: true,
    },
]

const simpleOptions = [
    {
        label: 'Opção A',
        value: 'A',
    },
    {
        label: 'Opção B',
        value: 'B',
    },
]

const roleOptions = [
    {
        id: 10,
        name: 'Administrador',
        blocked: false,
    },
    {
        id: 20,
        name: 'Operador',
        blocked: false,
    },
    {
        id: 30,
        name: 'Auditor',
        blocked: true,
    },
]

const basicCode = `<RadioGroup
  v-model="gender"
  id="gender"
  name="gender"
  label="Sexo"
  :options="genderOptions"
/>`

const horizontalCode = `<RadioGroup
  v-model="priority"
  id="priority"
  name="priority"
  label="Prioridade"
  orientation="horizontal"
  :options="priorityOptions"
/>`

const numericCode = `<RadioGroup
  v-model="level"
  id="level"
  name="level"
  label="Nível"
  :options="[
    { label: 'Nível 1', value: 1 },
    { label: 'Nível 2', value: 2 }
  ]"
/>`

const booleanCode = `<RadioGroup
  v-model="active"
  id="active"
  name="active"
  label="Situação"
  :options="[
    { label: 'Ativo', value: true },
    { label: 'Inativo', value: false }
  ]"
/>`

const optionDisabledCode = `<RadioGroup
  v-model="access"
  id="access"
  name="access"
  label="Perfil de acesso"
  :options="accessOptions"
/>`

const disabledCode = `<RadioGroup
  v-model="value"
  id="group"
  name="group"
  label="Grupo desabilitado"
  :options="options"
  disabled
/>`

const requiredCode = `<RadioGroup
  v-model="value"
  id="type"
  name="type"
  label="Tipo"
  :options="options"
  required
/>`

const hintCode = `<RadioGroup
  v-model="value"
  id="category"
  name="category"
  label="Categoria"
  hint="Selecione a categoria aplicável."
  :options="options"
/>`

const errorCode = `<RadioGroup
  v-model="value"
  id="category"
  name="category"
  label="Categoria"
  error="Selecione uma opção."
  :options="options"
  required
/>`

const customPropertiesCode = `<RadioGroup
  v-model="role"
  id="role"
  name="role"
  label="Perfil"
  option-label="name"
  option-value="id"
  option-disabled="blocked"
  :options="roleOptions"
/>`
</script>