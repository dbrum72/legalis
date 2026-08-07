<template>
    <PlaygroundSection title="CheckboxGroup"
        description="Grupo de seleção múltipla com preservação de tipos, estados individuais e layout configurável.">
        <PlaygroundExample title="Básico" :code="basicCode">
            <CheckboxGroup v-model="permissions" id="permissions" name="permissions" label="Permissões"
                :options="permissionOptions" />

            <p class="playground-value">
                Selecionado: {{ permissions }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Orientação horizontal" :code="horizontalCode">
            <CheckboxGroup v-model="channels" id="channels" name="channels" label="Canais" orientation="horizontal"
                :options="channelOptions" />
        </PlaygroundExample>

        <PlaygroundExample title="Valores numéricos" :code="numericCode">
            <CheckboxGroup v-model="levels" id="levels" name="levels" label="Níveis" :options="levelOptions" />

            <p class="playground-value">
                Valores: {{ levels }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Valores booleanos" :code="booleanCode">
            <CheckboxGroup v-model="booleanValues" id="boolean-values" name="boolean-values" label="Valores booleanos"
                :options="booleanOptions" />

            <p class="playground-value">
                Valores: {{ booleanValues }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Opção desabilitada" :code="optionDisabledCode">
            <CheckboxGroup v-model="features" id="features" name="features" label="Recursos"
                :options="featureOptions" />
        </PlaygroundExample>

        <PlaygroundExample title="Grupo desabilitado" :code="disabledCode">
            <CheckboxGroup v-model="disabledValues" id="disabled-checkbox-group" name="disabled-checkbox-group"
                label="Grupo desabilitado" :options="simpleOptions" disabled />
        </PlaygroundExample>

        <PlaygroundExample title="Obrigatório" :code="requiredCode">
            <CheckboxGroup v-model="requiredValues" id="required-checkbox-group" name="required-checkbox-group"
                label="Selecione pelo menos uma opção" :options="simpleOptions" required />
        </PlaygroundExample>

        <PlaygroundExample title="Hint" :code="hintCode">
            <CheckboxGroup v-model="hintValues" id="hint-checkbox-group" name="hint-checkbox-group" label="Categorias"
                hint="Você pode selecionar uma ou mais opções." :options="simpleOptions" />
        </PlaygroundExample>

        <PlaygroundExample title="Erro" :code="errorCode">
            <CheckboxGroup v-model="errorValues" id="error-checkbox-group" name="error-checkbox-group"
                label="Categorias" hint="Este texto é ocultado quando há erro." error="Selecione ao menos uma opção."
                :options="simpleOptions" required />
        </PlaygroundExample>

        <PlaygroundExample title="Propriedades personalizadas" :code="customPropertiesCode">
            <CheckboxGroup v-model="roles" id="roles" name="roles" label="Perfis" option-label="name" option-value="id"
                option-disabled="blocked" :options="roleOptions" />

            <p class="playground-value">
                IDs selecionados: {{ roles }}
            </p>
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { CheckboxGroup } from '@/components/forms'

import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const permissions = ref(['read'])
const channels = ref(['email'])
const levels = ref([1])
const booleanValues = ref([true])
const features = ref(['basic'])
const disabledValues = ref(['A'])
const requiredValues = ref([])
const hintValues = ref([])
const errorValues = ref([])
const roles = ref([10])

const permissionOptions = [
    {
        label: 'Leitura',
        value: 'read',
    },
    {
        label: 'Escrita',
        value: 'write',
    },
    {
        label: 'Exclusão',
        value: 'delete',
    },
]

const channelOptions = [
    {
        label: 'E-mail',
        value: 'email',
    },
    {
        label: 'SMS',
        value: 'sms',
    },
    {
        label: 'Push',
        value: 'push',
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
        label: 'True',
        value: true,
    },
    {
        label: 'False',
        value: false,
    },
]

const featureOptions = [
    {
        label: 'Básico',
        value: 'basic',
    },
    {
        label: 'Avançado',
        value: 'advanced',
    },
    {
        label: 'Experimental',
        value: 'experimental',
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

const basicCode = `<CheckboxGroup
  v-model="permissions"
  id="permissions"
  name="permissions"
  label="Permissões"
  :options="permissionOptions"
/>`

const horizontalCode = `<CheckboxGroup
  v-model="channels"
  id="channels"
  name="channels"
  label="Canais"
  orientation="horizontal"
  :options="channelOptions"
/>`

const numericCode = `<CheckboxGroup
  v-model="levels"
  id="levels"
  name="levels"
  label="Níveis"
  :options="[
    { label: 'Nível 1', value: 1 },
    { label: 'Nível 2', value: 2 }
  ]"
/>`

const booleanCode = `<CheckboxGroup
  v-model="values"
  id="values"
  name="values"
  label="Valores booleanos"
  :options="[
    { label: 'True', value: true },
    { label: 'False', value: false }
  ]"
/>`

const optionDisabledCode = `<CheckboxGroup
  v-model="features"
  id="features"
  name="features"
  label="Recursos"
  :options="featureOptions"
/>`

const disabledCode = `<CheckboxGroup
  v-model="values"
  id="group"
  name="group"
  label="Grupo desabilitado"
  :options="options"
  disabled
/>`

const requiredCode = `<CheckboxGroup
  v-model="values"
  id="required-group"
  name="required-group"
  label="Selecione pelo menos uma opção"
  :options="options"
  required
/>`

const hintCode = `<CheckboxGroup
  v-model="values"
  id="categories"
  name="categories"
  label="Categorias"
  hint="Você pode selecionar uma ou mais opções."
  :options="options"
/>`

const errorCode = `<CheckboxGroup
  v-model="values"
  id="categories"
  name="categories"
  label="Categorias"
  error="Selecione ao menos uma opção."
  :options="options"
  required
/>`

const customPropertiesCode = `<CheckboxGroup
  v-model="roles"
  id="roles"
  name="roles"
  label="Perfis"
  option-label="name"
  option-value="id"
  option-disabled="blocked"
  :options="roleOptions"
/>`
</script>

<style scoped>
.playground-value {
    margin-top: var(--space-2);

    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
</style>