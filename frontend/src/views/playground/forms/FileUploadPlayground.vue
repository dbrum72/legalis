<template>
    <PlaygroundSection title="AppFileUpload"
        description="Upload de arquivos com suporte a seleção única ou múltipla, limites e rejeição controlada.">
        <PlaygroundExample title="Arquivo único" :code="singleCode">
            <AppFileUpload v-model="singleFiles" id="single-upload" name="single-upload" label="Documento"
                accept=".pdf" />
        </PlaygroundExample>

        <PlaygroundExample title="Múltiplos arquivos" :code="multipleCode">
            <AppFileUpload v-model="multipleFiles" id="multiple-upload" name="multiple-upload" label="Anexos"
                multiple />
        </PlaygroundExample>

        <PlaygroundExample title="Limite de arquivos" :code="maxFilesCode">
            <AppFileUpload v-model="limitedFiles" id="limited-upload" name="limited-upload" label="Até 3 arquivos"
                multiple :max-files="3" />
        </PlaygroundExample>

        <PlaygroundExample title="Limite de tamanho" :code="maxSizeCode">
            <AppFileUpload v-model="sizeLimitedFiles" id="size-upload" name="size-upload" label="PDF de até 1 MB"
                accept=".pdf,application/pdf" :max-file-size="oneMegabyte" @reject="handleReject" />

            <p v-if="lastRejection" class="playground-value">
                Última rejeição:
                {{ lastRejection.file.name }}
                — {{ lastRejection.reason }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Obrigatório" :code="requiredCode">
            <AppFileUpload v-model="requiredFiles" id="required-upload" name="required-upload"
                label="Documento obrigatório" required />
        </PlaygroundExample>

        <PlaygroundExample title="Com hint" :code="hintCode">
            <AppFileUpload v-model="hintFiles" id="hint-upload" name="hint-upload" label="Documentos"
                hint="Selecione arquivos PDF de até 5 MB." accept=".pdf" />
        </PlaygroundExample>

        <PlaygroundExample title="Erro" :code="errorCode">
            <AppFileUpload v-model="errorFiles" id="error-upload" name="error-upload" label="Documento"
                error="Selecione um arquivo válido." required />
        </PlaygroundExample>

        <PlaygroundExample title="Desabilitado" :code="disabledCode">
            <AppFileUpload v-model="disabledFiles" id="disabled-upload" name="disabled-upload"
                label="Upload indisponível" disabled />
        </PlaygroundExample>

        <PlaygroundExample title="Eventos" :code="eventsCode">
            <AppFileUpload v-model="eventFiles" id="events-upload" name="events-upload" label="Anexos" multiple
                :max-files="2" @select="handleSelect" @remove="handleRemove" @reject="handleReject" />

            <p class="playground-value">
                Último evento: {{ lastEvent || 'nenhum' }}
            </p>
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import { AppFileUpload } from '@/components/forms'

import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const singleFiles = ref([])
const multipleFiles = ref([])
const limitedFiles = ref([])
const sizeLimitedFiles = ref([])
const requiredFiles = ref([])
const hintFiles = ref([])
const errorFiles = ref([])
const disabledFiles = ref([])
const eventFiles = ref([])

const lastEvent = ref('')
const lastRejection = ref(null)

const oneMegabyte = 1024 * 1024

function handleSelect(files) {
    lastEvent.value =
        `select: ${files.map(file => file.name).join(', ')}`
}

function handleRemove(file) {
    lastEvent.value = `remove: ${file.name}`
}

function handleReject(payload) {
    lastRejection.value = payload

    lastEvent.value =
        `reject: ${payload.file.name} (${payload.reason})`
}

const singleCode = `<AppFileUpload
  v-model="files"
  id="document"
  name="document"
  label="Documento"
  accept=".pdf"
/>`

const multipleCode = `<AppFileUpload
  v-model="files"
  id="attachments"
  name="attachments"
  label="Anexos"
  multiple
/>`

const maxFilesCode = `<AppFileUpload
  v-model="files"
  id="attachments"
  name="attachments"
  label="Até 3 arquivos"
  multiple
  :max-files="3"
/>`

const maxSizeCode = `<AppFileUpload
  v-model="files"
  id="document"
  name="document"
  label="PDF de até 1 MB"
  accept=".pdf,application/pdf"
  :max-file-size="1024 * 1024"
  @reject="handleReject"
/>`

const requiredCode = `<AppFileUpload
  v-model="files"
  id="document"
  name="document"
  label="Documento obrigatório"
  required
/>`

const hintCode = `<AppFileUpload
  v-model="files"
  id="documents"
  name="documents"
  label="Documentos"
  hint="Selecione arquivos PDF de até 5 MB."
  accept=".pdf"
/>`

const errorCode = `<AppFileUpload
  v-model="files"
  id="document"
  name="document"
  label="Documento"
  error="Selecione um arquivo válido."
  required
/>`

const disabledCode = `<AppFileUpload
  v-model="files"
  id="document"
  name="document"
  label="Upload indisponível"
  disabled
/>`

const eventsCode = `<AppFileUpload
  v-model="files"
  id="attachments"
  name="attachments"
  label="Anexos"
  multiple
  :max-files="2"
  @select="handleSelect"
  @remove="handleRemove"
  @reject="handleReject"
/>`
</script>

<style scoped>
.playground-value {
    margin-top: var(--space-2);

    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
</style>