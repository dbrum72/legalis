<template>
    <PlaygroundSection title="AppSwitch"
        description="Controle booleano para ativar ou desativar configurações com efeito imediato.">
        <PlaygroundExample title="Padrão" description="Switch booleano simples." :code="defaultCode">
            <AppSwitch v-model="notifications" id="playground-switch-default" name="notifications"
                label="Ativar notificações" />

            <p class="playground-value">
                Valor: {{ notifications }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Com hint" description="Texto auxiliar associado ao controle." :code="hintCode">
            <AppSwitch v-model="automaticSave" id="playground-switch-hint" name="automatic-save"
                label="Salvar automaticamente" hint="As alterações serão salvas enquanto você trabalha." />
        </PlaygroundExample>

        <PlaygroundExample title="Ativado" description="Estado inicial ativo." :code="checkedCode">
            <AppSwitch v-model="darkFeature" id="playground-switch-checked" name="feature" label="Recurso habilitado" />

            <p class="playground-value">
                Valor: {{ darkFeature }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Obrigatório" description="Switch marcado como obrigatório." :code="requiredCode">
            <AppSwitch v-model="requiredValue" id="playground-switch-required" name="required-switch"
                label="Ativar configuração obrigatória" required />
        </PlaygroundExample>

        <PlaygroundExample title="Erro" description="Estado inválido com mensagem associada." :code="errorCode">
            <AppSwitch v-model="errorValue" id="playground-switch-error" name="error-switch" label="Ativar configuração"
                error="Esta configuração é obrigatória." required />
        </PlaygroundExample>

        <PlaygroundExample title="Desabilitado" description="Switch indisponível para interação." :code="disabledCode">
            <AppSwitch v-model="disabledValue" id="playground-switch-disabled" name="disabled-switch"
                label="Configuração indisponível" disabled />
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppSwitch } from '@/components/forms'
import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const notifications = ref(false)
const automaticSave = ref(true)
const darkFeature = ref(true)
const requiredValue = ref(false)
const errorValue = ref(false)
const disabledValue = ref(true)

const defaultCode = `<AppSwitch
  v-model="notifications"
  id="notifications"
  name="notifications"
  label="Ativar notificações"
/>`

const hintCode = `<AppSwitch
  v-model="automaticSave"
  id="automatic-save"
  name="automatic-save"
  label="Salvar automaticamente"
  hint="As alterações serão salvas enquanto você trabalha."
/>`

const checkedCode = `<AppSwitch
  v-model="enabled"
  id="feature"
  name="feature"
  label="Recurso habilitado"
/>`

const requiredCode = `<AppSwitch
  v-model="enabled"
  id="required-switch"
  name="required-switch"
  label="Ativar configuração obrigatória"
  required
/>`

const errorCode = `<AppSwitch
  v-model="enabled"
  id="error-switch"
  name="error-switch"
  label="Ativar configuração"
  error="Esta configuração é obrigatória."
  required
/>`

const disabledCode = `<AppSwitch
  v-model="enabled"
  id="disabled-switch"
  name="disabled-switch"
  label="Configuração indisponível"
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