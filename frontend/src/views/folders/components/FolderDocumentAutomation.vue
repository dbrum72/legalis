<template>
    <section class="automation" aria-label="Documentos a partir de modelos Word">
        <AppButton variant="modal" :disabled="busy" @click="toggle">
            {{ opened ? 'Fechar modelos Word' : 'Gerar a partir de modelo Word' }}
        </AppButton>
        <div v-if="opened" class="automation__body">
            <h3>Gerar documento Word</h3>
            <p>Escolha um modelo, complete os dados e gere uma cópia Word na pasta.</p>
            <p v-if="loading" role="status">Carregando modelos…</p>
            <AppButton v-if="!loading && loadFailed" variant="action" @click="load"
                >Tentar novamente</AppButton
            >
            <template v-if="!loading && !loadFailed">
                <RouterLink
                    v-if="canManage"
                    :to="{ name: 'settings.document-templates' }"
                    target="_blank"
                    rel="opener"
                    >Gerenciar modelos (nova aba)</RouterLink
                >
                <p v-if="!templates.length">
                    Nenhum modelo disponível. Os modelos são cadastrados em Configurações → Modelos
                    de documentos.
                </p>
                <p v-if="refreshError" role="alert">
                    {{ refreshError }}
                    <AppButton variant="action" @click="refreshTemplates"
                        >Atualizar modelos</AppButton
                    >
                </p>
                <form v-if="templates.length" class="automation__form" @submit.prevent="preview">
                    <AppSelect
                        v-model="templateId"
                        id="word-template-select"
                        label="Modelo"
                        :options="templateOptions"
                        :disabled="busy"
                        placeholder="Selecione um modelo"
                    />
                    <template v-if="selected">
                        <AppSelect
                            v-if="needsParty"
                            v-model="partyId"
                            id="word-template-party"
                            label="Cliente utilizado no documento"
                            :options="context.parties"
                            :disabled="busy"
                            placeholder="Selecione o cliente e sua qualificação"
                        />
                        <AppInput
                            v-model="documentName"
                            id="word-document-name"
                            label="Nome do documento"
                            :maxlength="150"
                            :disabled="busy"
                        />
                        <AppButton
                            type="submit"
                            variant="modal"
                            :disabled="busy || (needsParty && !partyId)"
                            :loading="busy"
                            >{{ result ? 'Atualizar prévia' : 'Preencher e visualizar' }}</AppButton
                        >
                    </template>
                    <template v-if="result">
                        <h4>Dados do documento</h4>
                        <p>
                            Os ajustes abaixo valem somente para esta cópia. Revise os dados antes
                            de gerar.
                        </p>
                        <p>
                            Trechos condicionais são omitidos quando algum dos dados que os ativam
                            fica vazio. Após alterar os campos, atualize a prévia para conferir o
                            texto.
                        </p>
                        <AppTextarea
                            v-for="key in selected.fields"
                            :key="key"
                            :id="'word-value-' + key.replaceAll('.', '-')"
                            v-model="values[key]"
                            :label="
                                context.catalog[key] ||
                                key.replace('campo.', '').replaceAll('_', ' ')
                            "
                            :maxlength="20000"
                            :rows="2"
                            :disabled="busy"
                            @update:model-value="dirty = true"
                        />
                        <p v-if="result.missing.length" role="status">
                            Campos pendentes: {{ result.missing.join(', ') }}
                        </p>
                        <p v-if="dirty" role="status">
                            Atualize a prévia para conferir suas alterações.
                        </p>
                        <h4>Prévia do texto</h4>
                        <p>
                            A diagramação fica no Word. O texto abaixo inclui cabeçalhos e rodapés;
                            confira a paginação no arquivo exportado.
                        </p>
                        <pre class="automation__preview">{{ result.text }}</pre>
                        <AppButton
                            v-if="canManage"
                            type="button"
                            variant="action"
                            :disabled="
                                busy || dirty || result.missing.length > 0 || !documentName.trim()
                            "
                            :loading="busy"
                            @click="generate"
                            >Gerar Word e salvar na pasta</AppButton
                        >
                    </template>
                </form>
            </template>
            <p v-if="error" role="alert">{{ error }}</p>
            <p v-if="success" role="status">{{ success }}</p>
        </div>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { AppButton } from '@/components/ui'
import { AppInput, AppSelect, AppTextarea } from '@/components/forms'
import api from '@/api/client.js'
import { useAuthStore } from '@/stores/auth.js'
import { getCurrentTenant } from '@/api/tenant.js'

const props = defineProps({ folderId: { type: [Number, String], required: true } })
const emit = defineEmits(['generated'])
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('folders.update'))
const opened = ref(false),
    loading = ref(false),
    busy = ref(false),
    loadFailed = ref(false)
const templates = ref([]),
    context = ref({ catalog: {}, parties: [] })
const templateId = ref(null),
    partyId = ref(null)
const documentName = ref(''),
    values = ref({}),
    result = ref(null),
    dirty = ref(false)
const error = ref(''),
    success = ref('')
let epoch = 0
const selected = computed(() => templates.value.find((t) => t.id === templateId.value))
const needsParty = computed(() => selected.value?.fields.some((f) => f.startsWith('cliente.')))
const templateOptions = computed(() =>
    templates.value.map((t) => ({ value: t.id, label: `${t.name} — versão #${t.id}` })),
)
const refreshError = ref('')
let refreshEpoch = 0
const base = () => `/folders/${props.folderId}/document-generation`
const payload = () => ({
    template_id: templateId.value,
    party_id: partyId.value,
    values: values.value,
})
const message = (err) =>
    Object.values(err.response?.data?.errors || {})
        .flat()
        .join(' ') ||
    err.response?.data?.message ||
    'Não foi possível concluir. Tente novamente.'
function scope() {
    const version = ++epoch,
        folder = props.folderId,
        tenant = getCurrentTenant()
    return () => version === epoch && folder === props.folderId && tenant === getCurrentTenant()
}
function resetPreview() {
    result.value = null
    values.value = {}
    dirty.value = false
    error.value = ''
    success.value = ''
}
watch(templateId, () => {
    resetPreview()
    documentName.value = selected.value?.name || ''
})
watch(partyId, resetPreview)
watch(
    () => [props.folderId, auth.currentTenant],
    () => {
        epoch++
        refreshEpoch++
        refreshError.value = ''
        opened.value = false
        templates.value = []
        templateId.value = null
        partyId.value = null
        busy.value = false
        loading.value = false
        resetPreview()
    },
)
onBeforeUnmount(() => {
    epoch++
    refreshEpoch++
    window.removeEventListener('focus', onFocus)
})
onMounted(() => window.addEventListener('focus', onFocus))
function onFocus() {
    if (opened.value && !loading.value && !busy.value) void refreshTemplates()
}
async function refreshTemplates() {
    const id = ++refreshEpoch,
        folder = props.folderId,
        tenant = getCurrentTenant()
    const current = () =>
        id === refreshEpoch && folder === props.folderId && tenant === getCurrentTenant()
    refreshError.value = ''
    try {
        const { data } = await api.get('/document-templates')
        if (!current()) return
        templates.value = data
        if (templateId.value && !data.some((item) => item.id === templateId.value))
            templateId.value = null
    } catch {
        if (current())
            refreshError.value =
                'Não foi possível atualizar a lista de modelos. Seu preenchimento foi preservado.'
    }
}
async function toggle() {
    opened.value = !opened.value
    if (opened.value) await load()
}
async function load() {
    refreshEpoch++
    refreshError.value = ''
    const current = scope()
    loading.value = true
    error.value = ''
    loadFailed.value = false
    try {
        const [models, data] = await Promise.all([
            api.get('/document-templates'),
            api.get(`${base()}/context`),
        ])
        if (!current()) return
        templates.value = models.data
        context.value = data.data
    } catch (err) {
        if (current()) {
            error.value = message(err)
            loadFailed.value = true
        }
    } finally {
        if (current()) loading.value = false
    }
}
async function preview() {
    if (busy.value || !selected.value) return
    const current = scope()
    busy.value = true
    error.value = ''
    success.value = ''
    try {
        const response = await api.post(`${base()}/preview`, payload())
        if (!current()) return
        result.value = response.data
        values.value = { ...response.data.values }
        dirty.value = false
    } catch (err) {
        if (current()) {
            error.value = message(err)
            dirty.value = true
        }
    } finally {
        if (current()) busy.value = false
    }
}
async function generate() {
    if (busy.value || dirty.value || !result.value) return
    const current = scope()
    busy.value = true
    error.value = ''
    try {
        const response = await api.post(base(), {
            ...payload(),
            name: documentName.value,
            signature: result.value.signature,
        })
        if (!current()) return
        emit('generated', response.data)
        result.value = null
        values.value = {}
        success.value =
            'Documento salvo na pasta. Use Baixar na lista de documentos para abrir e revisar no Word.'
    } catch (err) {
        if (current()) {
            error.value = message(err)
            dirty.value = true
        }
    } finally {
        if (current()) busy.value = false
    }
}
</script>

<style scoped>
.automation {
    margin-block: 1rem;
    min-width: 0;
}
.automation__body,
.automation__form {
    display: grid;
    gap: 1rem;
    min-width: 0;
}
.automation__body {
    padding-block: 1rem;
}
.automation__body p,
.automation__body h3,
.automation__body h4 {
    margin: 0;
}
.automation__body summary {
    cursor: pointer;
    padding-block: 0.75rem;
    font-weight: 600;
}
.automation__body code {
    overflow-wrap: anywhere;
}
.automation__preview {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    max-height: 28rem;
    overflow: auto;
    padding: 1rem;
    border: 1px solid currentColor;
    border-radius: 0.5rem;
    font: inherit;
}
</style>
