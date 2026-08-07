<template>
    <PlaygroundSection title="AppDate"
        description="Campo de data com valor textual no formato YYYY-MM-DD, sem conversão de timezone.">
        <PlaygroundExample title="Básico" :code="basicCode">
            <AppDate v-model="hearingDate" id="hearing-date" name="hearing-date" label="Data da audiência" />

            <p class="playground-value">
                Valor: {{ hearingDate || "''" }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Com limites" :code="limitsCode">
            <AppDate v-model="deadline" id="deadline" name="deadline" label="Prazo" min="2026-08-01" max="2026-12-31" />

            <p class="playground-value">
                Valor: {{ deadline || "''" }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Obrigatório" :code="requiredCode">
            <AppDate v-model="requiredDate" id="required-date" name="required-date" label="Data obrigatória" required />
        </PlaygroundExample>

        <PlaygroundExample title="Com hint" :code="hintCode">
            <AppDate v-model="hintDate" id="hint-date" name="hint-date" label="Data de vencimento"
                hint="Informe a data no calendário." />
        </PlaygroundExample>

        <PlaygroundExample title="Erro" :code="errorCode">
            <AppDate v-model="errorDate" id="error-date" name="error-date" label="Data" error="Informe uma data válida."
                required />
        </PlaygroundExample>

        <PlaygroundExample title="Desabilitado" :code="disabledCode">
            <AppDate v-model="disabledDate" id="disabled-date" name="disabled-date" label="Data desabilitada"
                disabled />
        </PlaygroundExample>

        <PlaygroundExample title="Somente leitura" :code="readonlyCode">
            <AppDate v-model="readonlyDate" id="readonly-date" name="readonly-date" label="Data somente leitura"
                readonly />
        </PlaygroundExample>

        <PlaygroundExample title="Formato do valor" :code="formatCode">
            <AppDate v-model="formatDate" id="format-date" name="format-date" label="Data" />

            <p class="playground-value">
                modelValue: "{{ formatDate }}"
            </p>

            <p class="playground-value">
                Contrato: YYYY-MM-DD
            </p>
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppDate } from '@/components/forms'

import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const hearingDate = ref('2026-08-07')
const deadline = ref('2026-09-15')
const requiredDate = ref('')
const hintDate = ref('')
const errorDate = ref('')
const disabledDate = ref('2026-08-07')
const readonlyDate = ref('2026-08-07')
const formatDate = ref('2026-08-07')

const basicCode = `<AppDate
  v-model="hearingDate"
  id="hearing-date"
  name="hearing-date"
  label="Data da audiência"
/>`

const limitsCode = `<AppDate
  v-model="deadline"
  id="deadline"
  name="deadline"
  label="Prazo"
  min="2026-08-01"
  max="2026-12-31"
/>`

const requiredCode = `<AppDate
  v-model="date"
  id="date"
  name="date"
  label="Data obrigatória"
  required
/>`

const hintCode = `<AppDate
  v-model="date"
  id="date"
  name="date"
  label="Data de vencimento"
  hint="Informe a data no calendário."
/>`

const errorCode = `<AppDate
  v-model="date"
  id="date"
  name="date"
  label="Data"
  error="Informe uma data válida."
  required
/>`

const disabledCode = `<AppDate
  v-model="date"
  id="date"
  name="date"
  label="Data desabilitada"
  disabled
/>`

const readonlyCode = `<AppDate
  v-model="date"
  id="date"
  name="date"
  label="Data somente leitura"
  readonly
/>`

const formatCode = `const date = ref('2026-08-07')

// modelValue permanece exatamente:
// "2026-08-07"

// nenhuma conversão para Date,
// timestamp ou timezone é realizada.`
</script>

<style scoped>
.playground-value {
    margin-top: var(--space-2);

    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
</style>