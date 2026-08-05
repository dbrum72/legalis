<template>
  <PlaygroundSection
    title="AppNumber"
    description="Campo numérico com parsing, formatação, precisão e limites."
  >
    <PlaygroundExample
      title="Inteiro"
      description="Campo com incremento inteiro."
      :code="integerCode"
    >
      <AppNumber
        v-model="integerValue"
        id="playground-number-integer"
        label="Quantidade"
        :step="1"
      />

      <p class="playground-value">
        Valor: {{ integerValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Decimal"
      description="A precisão é inferida pelo step."
      :code="decimalCode"
    >
      <AppNumber
        v-model="decimalValue"
        id="playground-number-decimal"
        label="Coeficiente"
        :step="0.01"
      />

      <p class="playground-value">
        Valor: {{ decimalValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Com limites"
      description="Restringe o valor ao intervalo informado."
      :code="rangeCode"
    >
      <AppNumber
        v-model="rangeValue"
        id="playground-number-range"
        label="Percentual"
        :min="0"
        :max="100"
        :step="0.1"
      />

      <p class="playground-value">
        Valor: {{ rangeValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Campo obrigatório"
      description="Quando vazio, normaliza o valor para zero."
      :code="notEmptyCode"
    >
      <AppNumber
        v-model="notEmptyValue"
        id="playground-number-not-empty"
        label="Quantidade mínima"
        :allow-empty="false"
        :step="1"
      />

      <p class="playground-value">
        Valor: {{ notEmptyValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido com mensagem associada."
      :code="errorCode"
    >
      <AppNumber
        v-model="invalidValue"
        id="playground-number-error"
        label="Quantidade"
        error="Informe um valor entre 1 e 10."
        :min="1"
        :max="10"
        required
      />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppNumber } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const integerValue = ref(10)
const decimalValue = ref(1234.56)
const rangeValue = ref(50)
const notEmptyValue = ref(0)
const invalidValue = ref(15)

const integerCode = `<AppNumber
  v-model="quantity"
  id="quantity"
  label="Quantidade"
  :step="1"
/>`

const decimalCode = `<AppNumber
  v-model="coefficient"
  id="coefficient"
  label="Coeficiente"
  :step="0.01"
/>`

const rangeCode = `<AppNumber
  v-model="percentage"
  id="percentage"
  label="Percentual"
  :min="0"
  :max="100"
  :step="0.1"
/>`

const notEmptyCode = `<AppNumber
  v-model="quantity"
  id="quantity"
  label="Quantidade mínima"
  :allow-empty="false"
/>`

const errorCode = `<AppNumber
  v-model="quantity"
  id="quantity"
  label="Quantidade"
  error="Informe um valor entre 1 e 10."
  :min="1"
  :max="10"
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