<template>
    <PlaygroundSection title="AppCheckbox"
        description="Checkbox booleano com hint, erro, estado obrigatório e indeterminate.">
        <PlaygroundExample title="Padrão" description="Checkbox booleano simples." :code="defaultCode">
            <AppCheckbox v-model="accepted" id="playground-checkbox-default" label="Aceito os termos de uso" />

            <p class="playground-value">
                Valor: {{ accepted }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Com hint" description="Texto auxiliar associado ao controle." :code="hintCode">
            <AppCheckbox v-model="notifications" id="playground-checkbox-hint" label="Receber notificações"
                hint="Você poderá alterar essa preferência depois." />
        </PlaygroundExample>

        <PlaygroundExample title="Obrigatório" description="Checkbox marcado como obrigatório." :code="requiredCode">
            <AppCheckbox v-model="requiredValue" id="playground-checkbox-required" label="Declaro que li as condições"
                required />
        </PlaygroundExample>

        <PlaygroundExample title="Erro" description="Estado inválido com mensagem associada." :code="errorCode">
            <AppCheckbox v-model="errorValue" id="playground-checkbox-error" label="Aceito a política de privacidade"
                error="Aceite obrigatório." required />
        </PlaygroundExample>

        <PlaygroundExample title="Indeterminate" description="Estado visual intermediário." :code="indeterminateCode">
            <AppCheckbox v-model="partialValue" id="playground-checkbox-indeterminate" label="Selecionar todos"
                indeterminate />
        </PlaygroundExample>

        <PlaygroundExample title="Desabilitado" description="Checkbox desabilitado." :code="disabledCode">
            <AppCheckbox v-model="disabledValue" id="playground-checkbox-disabled" label="Opção indisponível"
                disabled />
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppCheckbox } from '@/components/forms'
import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const accepted = ref(false)
const notifications = ref(true)
const requiredValue = ref(false)
const errorValue = ref(false)
const partialValue = ref(false)
const disabledValue = ref(true)

const defaultCode = `<AppCheckbox
  v-model="accepted"
  id="accepted"
  label="Aceito os termos de uso"
/>`

const hintCode = `<AppCheckbox
  v-model="notifications"
  id="notifications"
  label="Receber notificações"
  hint="Você poderá alterar essa preferência depois."
/>`

const requiredCode = `<AppCheckbox
  v-model="accepted"
  id="accepted"
  label="Declaro que li as condições"
  required
/>`

const errorCode = `<AppCheckbox
  v-model="accepted"
  id="accepted"
  label="Aceito a política de privacidade"
  error="Aceite obrigatório."
  required
/>`

const indeterminateCode = `<AppCheckbox
  v-model="selected"
  id="select-all"
  label="Selecionar todos"
  indeterminate
/>`

const disabledCode = `<AppCheckbox
  v-model="enabled"
  id="disabled-option"
  label="Opção indisponível"
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