<template>
    <PageContainer>
        <div class="publications">
            <header class="publications__header">
                <div>
                    <h1 class="publications__title">Publicações</h1>
                    <p class="publications__description">Consulte e revise as comunicações importadas do DJEN.</p>
                </div>

                <AppButton type="button" variant="outline" @click="openMonitoring">OABs monitoradas</AppButton>
            </header>

            <form class="publications__filters" aria-label="Filtros de publicações" @submit.prevent="load">
                <AppSearch v-model="filters.search" id="publication-search" label="Busca"
                    placeholder="Processo, tribunal ou órgão" clearable />
                <AppSelect v-model="filters.review_status" id="publication-review-status" label="Revisão"
                    :options="reviewOptions" />
                <AppSelect v-model="filters.link_status" id="publication-link-status" label="Vínculo"
                    :options="linkOptions" />
                <AppDate v-model="filters.available_from" id="publication-from" label="Disponível desde" />
                <AppDate v-model="filters.available_to" id="publication-to" label="Disponível até" />

                <div class="publications__filter-actions">
                    <AppButton type="submit" :loading="store.fetching">Filtrar</AppButton>
                    <AppButton type="button" variant="ghost" @click="clearFilters">Limpar</AppButton>
                </div>
            </form>

            <div v-if="errorMessage" class="publications__error" role="alert">{{ errorMessage }}</div>
            <div v-if="successMessage" class="publications__success" role="status">{{ successMessage }}</div>

            <AppTable :columns="columns" :rows="store.publications"
                :empty-text="store.fetching ? 'Carregando publicações...' : 'Nenhuma publicação encontrada.'">
                <template #cell-available_on="{ value }">{{ formatDate(value) }}</template>
                <template #cell-process_number="{ value }">{{ value || 'Não informado' }}</template>
                <template #cell-review_status="{ value }">
                    <span class="publications__status" :class="`publications__status--${value}`">
                        {{ reviewLabel(value) }}
                    </span>
                </template>
                <template #cell-folder="{ value }">{{ value?.name || 'Não vinculada' }}</template>
                <template #cell-actions="{ row }">
                    <AppButton type="button" size="sm" variant="ghost" @click="showPublication(row)">Visualizar
                    </AppButton>
                </template>
            </AppTable>

            <p class="publications__summary">{{ store.pagination.total }} publicação(ões) encontrada(s).</p>

            <AppDialog :open="Boolean(selected)" title="Detalhes da publicação" size="lg" @close="closeDetails">
                <article v-if="selected" class="publication-detail">
                    <dl class="publication-detail__metadata">
                        <div>
                            <dt>Processo</dt>
                            <dd>{{ selected.process_number || 'Não informado' }}</dd>
                        </div>
                        <div>
                            <dt>Tribunal</dt>
                            <dd>{{ selected.court_acronym || '—' }}</dd>
                        </div>
                        <div>
                            <dt>Órgão</dt>
                            <dd>{{ selected.judicial_body || '—' }}</dd>
                        </div>
                        <div>
                            <dt>Disponibilização</dt>
                            <dd>{{ formatDate(selected.available_on) }}</dd>
                        </div>
                    </dl>

                    <section>
                        <h3>Conteúdo</h3>
                        <p class="publication-detail__content">{{ selected.content || 'Conteúdo não informado.' }}</p>
                    </section>

                    <section v-if="canReview" class="publication-detail__action">
                        <AppSelect v-model="selectedFolderId" id="publication-folder" label="Pasta vinculada"
                            placeholder="Selecione uma pasta" :options="folderOptions" />
                        <AppButton type="button" variant="highlight" :loading="saving" @click="saveFolder">Salvar vínculo
                        </AppButton>
                    </section>

                    <section v-if="canReview" class="publication-detail__action">
                        <AppButton type="button" :loading="saving" @click="setReviewStatus('reviewed')">Marcar como
                            revisada
                        </AppButton>
                        <AppButton type="button" variant="ghost" :loading="saving" @click="setReviewStatus('ignored')">
                            Ignorar
                        </AppButton>
                    </section>
                </article>
            </AppDialog>
        </div>
    </PageContainer>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'
import { AppDate, AppSearch, AppSelect } from '@/components/forms'
import { AppButton, AppDialog, AppTable } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import { useFoldersStore } from '@/stores/folders.js'
import { usePublicationsStore } from '@/stores/publications.js'

const router = useRouter()
const authStore = useAuthStore()
const foldersStore = useFoldersStore()
const store = usePublicationsStore()

const selected = ref(null)
const selectedFolderId = ref(null)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const filters = reactive({ search: '', review_status: null, link_status: null, available_from: '', available_to: '' })

const columns = [
    { key: 'available_on', label: 'Data' },
    { key: 'process_number', label: 'Processo' },
    { key: 'court_acronym', label: 'Tribunal' },
    { key: 'review_status', label: 'Revisão' },
    { key: 'folder', label: 'Pasta' },
    { key: 'actions', label: 'Ações', align: 'end' },
]
const reviewOptions = [
    { value: null, label: 'Todas' }, { value: 'pending_review', label: 'Pendente' },
    { value: 'reviewed', label: 'Revisada' }, { value: 'ignored', label: 'Ignorada' },
]
const linkOptions = [
    { value: null, label: 'Todos' }, { value: 'linked', label: 'Vinculadas' },
    { value: 'unlinked', label: 'Não vinculadas' },
]
const folderOptions = computed(() => foldersStore.folders.map((folder) => ({
    value: folder.id, label: folder.process_number ? `${folder.name} — ${folder.process_number}` : folder.name,
})))
const canReview = computed(() => authStore.hasPermission('publications.review'))

function formatDate(value) {
    if (!value) return '—'
    const [year, month, day] = String(value).slice(0, 10).split('-')
    return year && month && day ? `${day}/${month}/${year}` : '—'
}
function reviewLabel(value) {
    return { pending_review: 'Pendente', reviewed: 'Revisada', ignored: 'Ignorada' }[value] ?? '—'
}
function requestFilters() {
    return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null))
}
async function load() {
    errorMessage.value = ''
    try { await store.fetchPublications(requestFilters()) } catch { errorMessage.value = 'Não foi possível carregar as publicações.' }
}
function clearFilters() {
    Object.assign(filters, { search: '', review_status: null, link_status: null, available_from: '', available_to: '' })
    return load()
}
async function showPublication(row) {
    errorMessage.value = ''
    try {
        selected.value = await store.fetchPublication(row.id)
        selectedFolderId.value = selected.value.folder_id ?? null
    } catch { errorMessage.value = 'Não foi possível carregar os detalhes da publicação.' }
}
function closeDetails() { if (!saving.value) selected.value = null }
function openMonitoring() { return router.push({ name: 'publications.monitoring' }) }
async function saveFolder() {
    saving.value = true
    try {
        selected.value = await store.link(selected.value.id, selectedFolderId.value)
        successMessage.value = 'Vínculo atualizado com sucesso.'
    } catch { errorMessage.value = 'Não foi possível atualizar o vínculo.' } finally { saving.value = false }
}
async function setReviewStatus(status) {
    saving.value = true
    try {
        selected.value = await store.review(selected.value.id, status)
        successMessage.value = status === 'reviewed' ? 'Publicação revisada.' : 'Publicação ignorada.'
    } catch { errorMessage.value = 'Não foi possível revisar a publicação.' } finally { saving.value = false }
}

onMounted(async () => {
    await Promise.all([load(), canReview.value ? foldersStore.fetchFolders() : Promise.resolve()])
})
</script>

<style scoped>
.publications,
.publication-detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-6)
}

.publications__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4)
}

.publications__title {
    margin: 0;
    color: var(--color-text)
}

.publications__description,
.publications__summary {
    margin: var(--space-2) 0 0;
    color: var(--color-text-muted)
}

.publications__filters {
    display: grid;
    grid-template-columns: 2fr repeat(4, minmax(9rem, 1fr));
    align-items: end;
    gap: var(--space-3)
}

.publications__filter-actions,
.publication-detail__action {
    display: flex;
    align-items: flex-end;
    gap: var(--space-2)
}

.publications__filter-actions {
    grid-column: 1/-1;
    justify-content: flex-end
}

.publications__error,
.publications__success {
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-md)
}

.publications__error {
    border: 1px solid var(--color-danger);
    background: var(--color-danger-soft);
    color: var(--color-danger)
}

.publications__success {
    border: 1px solid var(--color-success);
    background: var(--color-success-soft);
    color: var(--color-success)
}

.publications__status {
    display: inline-flex;
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 600
}

.publications__status--pending_review {
    background: var(--color-surface-accent);
    color: var(--color-brand)
}

.publications__status--reviewed {
    background: var(--color-success-soft);
    color: var(--color-success)
}

.publications__status--ignored {
    background: var(--color-surface-muted);
    color: var(--color-text-muted)
}

.publication-detail__metadata {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-4);
    margin: 0
}

.publication-detail__metadata div {
    padding: var(--space-3);
    background: var(--color-surface-muted);
    border-radius: var(--radius-md)
}

.publication-detail__metadata dt {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm)
}

.publication-detail__metadata dd {
    margin: var(--space-1) 0 0;
    color: var(--color-text);
    font-weight: 600
}

.publication-detail h3 {
    margin: 0 0 var(--space-2)
}

.publication-detail__content {
    margin: 0;
    white-space: pre-wrap;
    line-height: 1.6
}

.publication-detail__action>*:first-child {
    flex: 1
}

@media(max-width:900px) {
    .publications__filters {
        grid-template-columns: repeat(2, 1fr)
    }

    .publications__header {
        flex-direction: column
    }

    .publication-detail__metadata {
        grid-template-columns: 1fr
    }
}
</style>
