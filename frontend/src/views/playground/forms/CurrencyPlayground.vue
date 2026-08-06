<template>
  <PlaygroundSection
    title="AppCurrency"
    description="Campo monetário com locale, moeda, precisão, limites e agrupamento."
  >
    <PlaygroundExample
      title="Real brasileiro"
      description="Configuração padrão com locale pt-BR e moeda BRL."
      :code="defaultCode"
    >
      <AppCurrency
        v-model="brlValue"
        id="playground-currency-brl"
        name="amount"
        label="Valor"
      />

      <p class="playground-value">
        Valor: {{ brlValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Dólar americano"
      description="Utiliza locale en-US e moeda USD."
      :code="usdCode"
    >
      <AppCurrency
        v-model="usdValue"
        id="playground-currency-usd"
        label="Valor em dólar"
        locale="en-US"
        currency="USD"
      />

      <p class="playground-value">
        Valor: {{ usdValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Sem símbolo"
      description="Mantém o comportamento monetário sem exibir a moeda."
      :code="withoutSymbolCode"
    >
      <AppCurrency
        v-model="withoutSymbolValue"
        id="playground-currency-without-symbol"
        label="Valor líquido"
        :show-currency="false"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Valor negativo"
      description="Permite números negativos quando explicitamente habilitado."
      :code="negativeCode"
    >
      <AppCurrency
        v-model="negativeValue"
        id="playground-currency-negative"
        label="Ajuste financeiro"
        :allow-negative="true"
      />

      <p class="playground-value">
        Valor: {{ negativeValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Com limites"
      description="Restringe o valor ao intervalo configurado."
      :code="rangeCode"
    >
      <AppCurrency
        v-model="rangeValue"
        id="playground-currency-range"
        label="Limite de despesa"
        :min="0"
        :max="10000"
      />

      <p class="playground-value">
        Valor: {{ rangeValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Precisão personalizada"
      description="Exibe três casas decimais."
      :code="precisionCode"
    >
      <AppCurrency
        v-model="precisionValue"
        id="playground-currency-precision"
        label="Cotação"
        :precision="3"
      />

      <p class="playground-value">
        Valor: {{ precisionValue ?? 'null' }}
      </p>
    </PlaygroundExample>

    <PlaygroundExample
      title="Campo obrigatório"
      description="Quando vazio, normaliza o valor para zero."
      :code="notEmptyCode"
    >
      <AppCurrency
        v-model="notEmptyValue"
        id="playground-currency-not-empty"
        label="Valor obrigatório"
        :allow-empty="false"
      />
    </PlaygroundExample>

    <PlaygroundExample
      title="Erro"
      description="Estado inválido com mensagem associada."
      :code="errorCode"
    >
      <AppCurrency
        v-model="invalidValue"
        id="playground-currency-error"
        label="Valor"
        error="Informe um valor válido."
        required
      />
    </PlaygroundExample>
  </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppCurrency } from '@/components/forms'
import {
  PlaygroundExample,
  PlaygroundSection,
} from '@/playground/components'

const brlValue = ref(1234.56)
const usdValue = ref(1234.56)
const withoutSymbolValue = ref(500)
const negativeValue = ref(-250.75)
const rangeValue = ref(7500)
const precisionValue = ref(5.432)
const notEmptyValue = ref(0)
const invalidValue = ref(null)

const defaultCode = `<AppCurrency
  v-model="amount"
  id="amount"
  label="Valor"
/>`

const usdCode = `<AppCurrency
  v-model="amount"
  id="amount-usd"
  label="Valor em dólar"
  locale="en-US"
  currency="USD"
/>`

const withoutSymbolCode = `<AppCurrency
  v-model="amount"
  id="amount"
  label="Valor líquido"
  :show-currency="false"
/>`

const negativeCode = `<AppCurrency
  v-model="amount"
  id="amount"
  label="Ajuste financeiro"
  :allow-negative="true"
/>`

const rangeCode = `<AppCurrency
  v-model="amount"
  id="amount"
  label="Limite de despesa"
  :min="0"
  :max="10000"
/>`

const precisionCode = `<AppCurrency
  v-model="exchangeRate"
  id="exchange-rate"
  label="Cotação"
  :precision="3"
/>`

const notEmptyCode = `<AppCurrency
  v-model="amount"
  id="amount"
  label="Valor obrigatório"
  :allow-empty="false"
/>`

const errorCode = `<AppCurrency
  v-model="amount"
  id="amount"
  label="Valor"
  error="Informe um valor válido."
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