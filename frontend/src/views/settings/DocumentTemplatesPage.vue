<template>
    <PageContainer>
        <div class="templates-page">
            <header>
                <h1>Modelos de documentos</h1>
                <p>
                    Modelos Word de {{ auth.organization?.name || 'seu escritório' }}. Use-os na aba
                    Documentos de qualquer pasta.
                </p>
                <RouterLink :to="{ name: 'settings' }">Voltar às configurações</RouterLink>
            </header>
            <p v-if="loading" role="status">Carregando modelos…</p>
            <div v-if="loadError" role="alert">
                <p>{{ loadError }}</p>
                <AppButton variant="action" @click="load">Tentar novamente</AppButton>
            </div>
            <template v-if="!loading && !loadError">
                <AppCard>
                    <details>
                        <summary>Como preparar o modelo</summary>
                        <p>
                            Insira os marcadores abaixo no Word. Para texto livre, use
                            <code v-pre>{{ campo.fatos }}</code
                            >, <code v-pre>{{ campo.pedidos }}</code> ou outro nome em minúsculas,
                            sem espaços ou acentos.
                        </p>
                        <p>
                            Campos fora de blocos condicionais são obrigatórios. Outras partes e
                            signatários podem usar campos livres.
                        </p>
                        <p>
                            Para omitir um trecho quando faltar algum dado, envolva o texto em
                            <code v-pre
                                >{{#se cliente.identidade cliente.orgao_emissor}}, RG nº
                                {{ cliente.identidade }}/{{ cliente.orgao_emissor }}{{/se}}</code
                            >. O trecho aparece somente quando todos os campos indicados após #se
                            estão preenchidos.
                        </p>
                        <p>
                            Para mencionar uma ação somente quando houver número, use
                            <code v-pre
                                >{{#se processo.numero}} especialmente para efetuar sua defesa em
                                Ação {{ processo.numero }}{{/se}}</code
                            >. Inclua os espaços e a pontuação opcionais dentro do bloco. Cada bloco
                            deve começar e terminar no mesmo parágrafo, sem outros blocos dentro
                            dele.
                        </p>
                        <ul>
                            <li v-for="(label, key) in fields" :key="key">
                                <code>{{ marker(key) }}</code> — {{ label }}
                            </li>
                        </ul>
                        <p>
                            Mantenha cada marcador no mesmo parágrafo. Corpo, tabelas, cabeçalhos e
                            rodapés são aceitos. Aceite as revisões e converta campos dinâmicos do
                            Word em texto antes de importar.
                        </p>
                    </details>
                </AppCard>
                <AppCard v-if="canImport">
                    <h2>{{ replacing ? 'Substituir modelo .docx' : 'Importar modelo .docx' }}</h2>
                    <p v-if="replacing">
                        Substituindo {{ replacing.name }} (versão #{{ replacing.id }}). O anterior
                        será arquivado somente após validar e salvar o novo. Os documentos gerados
                        serão preservados.
                    </p>
                    <form class="templates-page__form" @submit.prevent="upload">
                        <AppInput
                            id="template-name"
                            v-model="name"
                            label="Nome do modelo"
                            required
                            :maxlength="150"
                            :disabled="busy"
                        />
                        <AppFileUpload
                            id="template-file"
                            v-model="files"
                            label="Modelo Word"
                            accept=".docx"
                            :disabled="busy"
                        />
                        <p>
                            Até 10 MB. Cada importação cria uma versão independente; os documentos
                            já gerados são preservados.
                        </p>
                        <AppButton
                            type="submit"
                            variant="action"
                            :disabled="busy || !name.trim() || !files.length"
                            :loading="busy"
                            >{{ replacing ? 'Salvar substituto' : 'Importar modelo' }}</AppButton
                        >
                        <AppButton
                            v-if="replacing"
                            variant="route"
                            :disabled="busy"
                            @click="cancelReplacement"
                            >Cancelar substituição</AppButton
                        >
                    </form>
                </AppCard>
                <AppCard class="templates-page__catalog">
                    <h2>Modelos disponíveis</h2>
                    <label class="templates-page__archive-filter"
                        ><input v-model="showArchived" type="checkbox" /> Mostrar arquivados</label
                    >
                    <div v-if="removing" class="templates-page__confirmation" role="alert">
                        <p>
                            {{
                                Number(removing.generations_count) > 0
                                    ? 'Arquivar'
                                    : 'Excluir definitivamente'
                            }}
                            “{{ removing.name }}”?
                            {{
                                Number(removing.generations_count) > 0
                                    ? 'O original e o histórico serão preservados.'
                                    : 'Este modelo ainda não possui documentos gerados.'
                            }}
                        </p>
                        <div class="templates-page__actions">
                            <AppButton variant="action" :disabled="busy" @click="remove"
                                >Confirmar</AppButton
                            >
                            <AppButton variant="route" :disabled="busy" @click="removing = null"
                                >Cancelar</AppButton
                            >
                        </div>
                    </div>
                    <AppSearch
                        id="template-search"
                        v-model="search"
                        label="Buscar modelos"
                        placeholder="Nome do modelo"
                    />
                    <p v-if="!templates.length" role="status">
                        Nenhum modelo importado neste escritório.
                    </p>
                    <p v-else-if="!filtered.length" role="status">
                        Nenhum modelo encontrado para esta busca.
                    </p>
                    <ul v-else class="templates-page__list">
                        <li v-for="item in filtered" :key="item.id">
                            <div>
                                <strong>{{ item.name }}</strong>
                                <span v-if="item.archived_at"> — Arquivado</span>
                                <p>
                                    Versão #{{ item.id }} · {{ formatDate(item.created_at) }} ·
                                    {{ item.fields.length }}
                                    {{ item.fields.length === 1 ? 'campo' : 'campos' }}
                                </p>
                            </div>
                            <AppButton variant="route" :disabled="busy" @click="download(item)"
                                >Baixar original</AppButton
                            >
                            <template v-if="canImport && !item.archived_at">
                                <AppButton
                                    variant="action"
                                    :disabled="busy"
                                    @click="startReplacement(item)"
                                    >Substituir modelo</AppButton
                                >
                                <AppButton
                                    variant="route"
                                    :disabled="busy"
                                    @click="removing = item"
                                    >{{
                                        Number(item.generations_count) > 0
                                            ? 'Arquivar modelo'
                                            : 'Excluir modelo'
                                    }}</AppButton
                                >
                            </template>
                        </li>
                    </ul>
                </AppCard>
            </template>
            <p v-if="error" role="alert">{{ error }}</p>
            <p v-if="success" role="status">{{ success }}</p>
        </div>
    </PageContainer>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import PageContainer from '@/components/layout/PageContainer/index.vue'
import { AppButton, AppCard } from '@/components/ui'
import { AppInput, AppFileUpload, AppSearch } from '@/components/forms'
import { useAuthStore } from '@/stores/auth.js'
import {
    listDocumentTemplates,
    getDocumentTemplateFields,
    importDocumentTemplate,
    downloadDocumentTemplate,
    replaceDocumentTemplate,
    removeDocumentTemplate,
} from '@/api/document-templates.js'

const auth = useAuthStore()
const canImport = computed(
    () => auth.hasPermission('documents.generate') && auth.hasPermission('folders.update'),
)
const templates = ref([]),
    replacing = ref(null),
    removing = ref(null),
    showArchived = ref(false),
    fields = ref({}),
    name = ref(''),
    files = ref([]),
    search = ref('')
const loading = ref(false),
    busy = ref(false),
    loadError = ref(''),
    error = ref(''),
    success = ref('')
let epoch = 0
const filtered = computed(() =>
    templates.value.filter(
        (item) =>
            (showArchived.value || !item.archived_at) &&
            item.name
                .toLocaleLowerCase('pt-BR')
                .includes(search.value.trim().toLocaleLowerCase('pt-BR')),
    ),
)
const marker = (key) => '{{ ' + key + ' }}'
const formatDate = (value) =>
    value ? new Date(value).toLocaleDateString('pt-BR') : 'Data não informada'
const message = (err) =>
    Object.values(err.response?.data?.errors || {})
        .flat()
        .join(' ') ||
    err.response?.data?.message ||
    'Não foi possível concluir. Tente novamente.'
function scope() {
    const id = ++epoch,
        tenant = auth.currentTenant
    return () => id === epoch && tenant === auth.currentTenant
}
async function load() {
    const current = scope()
    loading.value = true
    loadError.value = ''
    try {
        const [models, catalog] = await Promise.all([
            listDocumentTemplates({ include_archived: true }),
            getDocumentTemplateFields(),
        ])
        if (!current()) return
        templates.value = models.data
        fields.value = catalog.data
    } catch (err) {
        if (current()) loadError.value = message(err)
    } finally {
        if (current()) loading.value = false
    }
}
watch(
    () => auth.currentTenant,
    () => {
        epoch++
        templates.value = []
        replacing.value = null
        removing.value = null
        showArchived.value = false
        fields.value = {}
        name.value = ''
        files.value = []
        search.value = ''
        error.value = ''
        success.value = ''
        busy.value = false
        void load()
    },
    { immediate: true },
)
onBeforeUnmount(() => {
    epoch++
})
async function upload() {
    if (busy.value || !canImport.value || !files.value.length) return
    const current = scope()
    busy.value = true
    error.value = ''
    success.value = ''
    const data = new FormData()
    data.append('name', name.value)
    data.append('file', files.value[0])
    try {
        const previous = replacing.value
        const response = previous
            ? await replaceDocumentTemplate(previous.id, data)
            : await importDocumentTemplate(data)
        if (!current()) return
        if (previous) previous.archived_at = new Date().toISOString()
        templates.value.unshift(response.data)
        files.value = []
        name.value = ''
        search.value = ''
        replacing.value = null
        success.value = previous
            ? 'Modelo substituído. O anterior foi arquivado e os documentos gerados foram preservados.'
            : 'Modelo importado. Ele já está disponível na aba Documentos das pastas.'
    } catch (err) {
        if (current()) error.value = message(err)
    } finally {
        if (current()) busy.value = false
    }
}
async function download(item) {
    if (busy.value) return
    const current = scope()
    busy.value = true
    error.value = ''
    try {
        const response = await downloadDocumentTemplate(item.id)
        if (!current()) return
        const url = URL.createObjectURL(response.data),
            link = document.createElement('a')
        link.href = url
        link.download = `modelo-${item.id}.docx`
        link.click()
        setTimeout(() => URL.revokeObjectURL(url), 1000)
    } catch (err) {
        if (current()) error.value = message(err)
    } finally {
        if (current()) busy.value = false
    }
}
function startReplacement(item) {
    replacing.value = item
    removing.value = null
    name.value = item.name
    files.value = []
    error.value = ''
    success.value = ''
    document.getElementById('template-name')?.focus()
}
function cancelReplacement() {
    replacing.value = null
    name.value = ''
    files.value = []
}
async function remove() {
    if (busy.value || !canImport.value || !removing.value) return
    const current = scope(),
        item = removing.value
    busy.value = true
    error.value = ''
    success.value = ''
    try {
        const { data } = await removeDocumentTemplate(item.id)
        if (!current()) return
        if (data.archived) item.archived_at = new Date().toISOString()
        else templates.value = templates.value.filter((model) => model.id !== item.id)
        if (replacing.value?.id === item.id) cancelReplacement()
        removing.value = null
        success.value = data.archived
            ? 'Modelo arquivado. O original e os documentos gerados foram preservados.'
            : 'Modelo excluído.'
    } catch (err) {
        if (current()) error.value = message(err)
    } finally {
        if (current()) busy.value = false
    }
}
</script>

<style scoped>
.templates-page,
.templates-page__form {
    display: grid;
    gap: var(--space-4);
    min-width: 0;
}
.templates-page h1,
.templates-page h2,
.templates-page strong {
    color: var(--color-brand);
}
.templates-page p {
    color: var(--color-text-muted);
    line-height: 1.6;
}
.templates-page summary {
    cursor: pointer;
    font-weight: 600;
}
.templates-page code {
    overflow-wrap: anywhere;
}
.templates-page__list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.templates-page__catalog :deep(.card__body) {
    display: grid;
    gap: var(--space-4);
    min-width: 0;
}
.templates-page__catalog h2,
.templates-page__confirmation p {
    margin: 0;
}
.templates-page__archive-filter {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    width: fit-content;
}
.templates-page__archive-filter input {
    margin: 0;
}
.templates-page__confirmation {
    display: grid;
    gap: var(--space-3);
}
.templates-page__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
}
.templates-page__list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--space-3);
    padding-block: var(--space-4);
    border-bottom: 1px solid var(--color-border);
    overflow-wrap: anywhere;
}
</style>
