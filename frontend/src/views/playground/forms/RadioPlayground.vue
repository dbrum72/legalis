<template>
    <PlaygroundSection title="AppRadio" description="Opção de seleção exclusiva com preservação de tipos.">
        <PlaygroundExample title="Grupo básico" description="Radios compartilhando o mesmo modelValue e name."
            :code="defaultCode">
            <div class="radio-group">
                <AppRadio v-model="selectedStatus" id="playground-radio-active" name="playground-status" label="Ativo"
                    value="active" />

                <AppRadio v-model="selectedStatus" id="playground-radio-inactive" name="playground-status"
                    label="Inativo" value="inactive" />
            </div>

            <p class="playground-value">
                Valor: {{ selectedStatus ?? 'null' }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Valores numéricos" description="Preserva o tipo number no v-model."
            :code="numericCode">
            <div class="radio-group">
                <AppRadio v-model="selectedPriority" id="playground-radio-priority-low" name="playground-priority"
                    label="Baixa" :value="1" />

                <AppRadio v-model="selectedPriority" id="playground-radio-priority-high" name="playground-priority"
                    label="Alta" :value="2" />
            </div>

            <p class="playground-value">
                Valor: {{ selectedPriority ?? 'null' }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Com hint" description="Texto auxiliar associado à opção." :code="hintCode">
            <AppRadio v-model="deliveryType" id="playground-radio-delivery" name="playground-delivery"
                label="Entrega expressa" value="express" hint="Prazo estimado de até dois dias úteis." />
        </PlaygroundExample>

        <PlaygroundExample title="Obrigatório" description="Opção marcada como obrigatória." :code="requiredCode">
            <AppRadio v-model="requiredValue" id="playground-radio-required" name="playground-required"
                label="Concordo com esta opção" value="accepted" required />
        </PlaygroundExample>

        <PlaygroundExample title="Erro" description="Estado inválido com mensagem associada." :code="errorCode">
            <AppRadio v-model="errorValue" id="playground-radio-error" name="playground-error"
                label="Seleção obrigatória" value="selected" error="Escolha uma opção." required />
        </PlaygroundExample>

        <PlaygroundExample title="Desabilitado" description="Opção indisponível para interação." :code="disabledCode">
            <AppRadio v-model="disabledValue" id="playground-radio-disabled" name="playground-disabled"
                label="Opção indisponível" value="disabled" disabled />
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppRadio } from '@/components/forms'
import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const selectedStatus = ref('active')
const selectedPriority = ref(1)
const deliveryType = ref(null)
const requiredValue = ref(null)
const errorValue = ref(null)
const disabledValue = ref('disabled')

const defaultCode = `<div>
  <AppRadio
    v-model="status"
    id="status-active"
    name="status"
    label="Ativo"
    value="active"
  />

  <AppRadio
    v-model="status"
    id="status-inactive"
    name="status"
    label="Inativo"
    value="inactive"
  />
</div>`

const numericCode = `<AppRadio
  v-model="priority"
  id="priority-high"
  name="priority"
  label="Alta"
  :value="2"
/>`

const hintCode = `<AppRadio
  v-model="deliveryType"
  id="delivery-express"
  name="delivery"
  label="Entrega expressa"
  value="express"
  hint="Prazo estimado de até dois dias úteis."
/>`

const requiredCode = `<AppRadio
  v-model="accepted"
  id="accepted"
  name="acceptance"
  label="Concordo com esta opção"
  value="accepted"
  required
/>`

const errorCode = `<AppRadio
  v-model="selection"
  id="selection"
  name="selection"
  label="Seleção obrigatória"
  value="selected"
  error="Escolha uma opção."
  required
/>`

const disabledCode = `<AppRadio
  v-model="selection"
  id="disabled-option"
  name="selection"
  label="Opção indisponível"
  value="disabled"
  disabled
/>`
</script>

<style scoped>
.radio-group {
    display: grid;
    gap: var(--space-3);
}

.playground-value {
    margin-top: var(--space-2);

    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
</style>