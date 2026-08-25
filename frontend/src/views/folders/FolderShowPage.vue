<template>
    <PageContainer>
        <div class="folder-show-page">
            <header class="folder-show-page__header">
                <div class="folder-show-page__heading">
                    <h1 class="folder-show-page__title">
                        Detalhes da pasta
                    </h1>

                    <p class="folder-show-page__description">
                        Consulte os dados da pasta jurídica e suas partes.
                    </p>
                </div>

                <div class="folder-show-page__actions">
                    <AppButton type="button" variant="ghost" @click="goBack">
                        Voltar
                    </AppButton>

                    <AppButton v-if="canUpdate" type="button" variant="primary" @click="goToEdit">
                        Editar
                    </AppButton>
                </div>
            </header>

            <div v-if="loadError" class="folder-show-page__error" role="alert">
                {{ loadError }}
            </div>

            <template v-if="folder">
                <AppTabs v-model="activeSection" :items="sections" aria-label="Seções da pasta" />

                <!--
                |--------------------------------------------------------------------------
                | Visão geral
                |--------------------------------------------------------------------------
                -->

                <template v-if="activeSection === 'overview'">
                    <AppCard>
                        <section class="folder-show-page__section" aria-labelledby="folder-operational-title">
                            <div class="folder-show-page__section-heading">
                                <div>
                                    <h2 id="folder-operational-title" class="folder-show-page__section-title">
                                        Visão operacional
                                    </h2>

                                    <p class="folder-show-page__section-description">
                                        Resumo dos principais pontos de atenção desta pasta.
                                    </p>
                                </div>
                            </div>

                            <div class="folder-show-page__summary-grid">
                                <button type="button"
                                    class="folder-show-page__summary-item folder-show-page__summary-item--documents"
                                    data-testid="folder-summary-documents" @click="activeSection = 'documents'">
                                    <span class="folder-show-page__summary-label">
                                        Documentos
                                    </span>

                                    <strong class="folder-show-page__summary-value">
                                        {{ summary.documents_count }}
                                    </strong>
                                </button>

                                <button type="button"
                                    class="folder-show-page__summary-item folder-show-page__summary-item--tasks"
                                    data-testid="folder-summary-tasks" @click="activeSection = 'tasks'">
                                    <span class="folder-show-page__summary-label">
                                        Tarefas pendentes
                                    </span>

                                    <strong class="folder-show-page__summary-value">
                                        {{ summary.pending_tasks_count }}
                                    </strong>
                                </button>

                                <button type="button"
                                    class="folder-show-page__summary-item folder-show-page__summary-item--deadlines"
                                    data-testid="folder-summary-deadlines" @click="activeSection = 'deadlines'">
                                    <span class="folder-show-page__summary-label">
                                        Prazos pendentes
                                    </span>

                                    <strong class="folder-show-page__summary-value">
                                        {{ summary.pending_deadlines_count }}
                                    </strong>
                                </button>
                            </div>

                            <div class="folder-show-page__attention" data-testid="folder-attention">
                                <header class="folder-show-page__attention-header">
                                    <div>
                                        <h3 class="folder-show-page__attention-title">
                                            Atenção jurídica
                                        </h3>

                                        <p class="folder-show-page__attention-description">
                                            Itens que exigem acompanhamento prioritário.
                                        </p>
                                    </div>
                                </header>

                                <div class="folder-show-page__attention-grid">
                                    <section class="folder-show-page__attention-group"
                                        data-testid="folder-attention-deadlines"
                                        aria-labelledby="folder-attention-deadlines-title">
                                        <header class="folder-show-page__attention-group-header">
                                            <h4 id="folder-attention-deadlines-title"
                                                class="folder-show-page__attention-group-title">
                                                Prazos prioritários
                                            </h4>

                                            <button type="button" class="folder-show-page__attention-link"
                                                data-testid="folder-attention-deadlines-all"
                                                @click="activeSection = 'deadlines'">
                                                Ver todos
                                                <span aria-hidden="true">→</span>
                                            </button>
                                        </header>

                                        <div v-if="summary.attention.deadlines.length"
                                            class="folder-show-page__attention-list">
                                            <article v-for="deadline in summary.attention.deadlines" :key="deadline.id"
                                                class="folder-show-page__attention-item">
                                                <span class="folder-show-page__attention-status" :class="[
                                                    `folder-show-page__attention-status--${deadline.urgency}`,
                                                ]">
                                                    {{ urgencyLabel(deadline.urgency) }}
                                                </span>

                                                <div class="folder-show-page__attention-content">
                                                    <strong class="folder-show-page__attention-item-title">
                                                        {{ deadline.title }}
                                                    </strong>
                                                </div>

                                                <time v-if="deadline.due_at" class="folder-show-page__attention-date"
                                                    :datetime="deadline.due_at">
                                                    {{ formatShortDate(deadline.due_at) }}
                                                </time>
                                            </article>
                                        </div>

                                        <p v-else class="folder-show-page__attention-empty">
                                            Nenhum prazo pendente exige atenção.
                                        </p>
                                    </section>

                                    <section class="folder-show-page__attention-group"
                                        data-testid="folder-attention-tasks"
                                        aria-labelledby="folder-attention-tasks-title">
                                        <header class="folder-show-page__attention-group-header">
                                            <h4 id="folder-attention-tasks-title"
                                                class="folder-show-page__attention-group-title">
                                                Tarefas prioritárias
                                            </h4>

                                            <button type="button" class="folder-show-page__attention-link"
                                                data-testid="folder-attention-tasks-all"
                                                @click="activeSection = 'tasks'">
                                                Ver todas
                                                <span aria-hidden="true">→</span>
                                            </button>
                                        </header>

                                        <div v-if="summary.attention.tasks.length"
                                            class="folder-show-page__attention-list">
                                            <article v-for="task in summary.attention.tasks" :key="task.id"
                                                class="folder-show-page__attention-item">
                                                <span class="folder-show-page__attention-status" :class="[
                                                    `folder-show-page__attention-status--${task.urgency}`,
                                                ]">
                                                    {{ urgencyLabel(task.urgency) }}
                                                </span>

                                                <div class="folder-show-page__attention-content">
                                                    <strong class="folder-show-page__attention-item-title">
                                                        {{ task.title }}
                                                    </strong>

                                                    <span class="folder-show-page__attention-priority">
                                                        {{ folderPriorityLabel(task.priority) }}
                                                    </span>
                                                </div>

                                                <time v-if="task.due_at" class="folder-show-page__attention-date"
                                                    :datetime="task.due_at">
                                                    {{ formatShortDate(task.due_at) }}
                                                </time>

                                                <span v-else class="folder-show-page__attention-date">
                                                    Sem vencimento
                                                </span>
                                            </article>
                                        </div>

                                        <p v-else class="folder-show-page__attention-empty">
                                            Nenhuma tarefa pendente exige atenção.
                                        </p>
                                    </section>
                                </div>
                            </div>

                            <div class="folder-show-page__highlights-grid">
                                <button type="button"
                                    class="folder-show-page__highlight folder-show-page__highlight--event"
                                    data-testid="folder-summary-next-event" @click="activeSection = 'events'">
                                    <div class="folder-show-page__highlight-heading">
                                        <span class="folder-show-page__highlight-label">
                                            Próximo compromisso
                                        </span>

                                        <span v-if="summary.next_event" class="folder-show-page__highlight-type">
                                            {{ folderEventTypeLabel(summary.next_event.type) }}
                                        </span>
                                    </div>

                                    <template v-if="summary.next_event">
                                        <strong class="folder-show-page__highlight-title">
                                            {{ summary.next_event.title }}
                                        </strong>

                                        <div class="folder-show-page__highlight-meta">
                                            <span v-if="summary.next_event.starts_at">
                                                {{
                                                    formatShortDateTime(
                                                        summary.next_event.starts_at,
                                                    )
                                                }}
                                            </span>

                                            <span v-if="summary.next_event.location">
                                                {{ summary.next_event.location }}
                                            </span>
                                        </div>
                                    </template>

                                    <p v-else class="folder-show-page__highlight-empty">
                                        Nenhum compromisso agendado.
                                    </p>
                                </button>

                                <button type="button"
                                    class="folder-show-page__highlight folder-show-page__highlight--movement"
                                    data-testid="folder-summary-latest-movement" @click="activeSection = 'movements'">
                                    <div class="folder-show-page__highlight-heading">
                                        <span class="folder-show-page__highlight-label">
                                            Última movimentação
                                        </span>
                                    </div>

                                    <template v-if="summary.latest_movement">
                                        <strong class="folder-show-page__highlight-title">
                                            {{ summary.latest_movement.title }}
                                        </strong>

                                        <div class="folder-show-page__highlight-meta">
                                            <span v-if="summary.latest_movement.occurred_at">
                                                {{
                                                    formatShortDateTime(
                                                        summary.latest_movement.occurred_at,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <p v-if="summary.latest_movement.description"
                                            class="folder-show-page__highlight-description">
                                            {{ summary.latest_movement.description }}
                                        </p>
                                    </template>

                                    <p v-else class="folder-show-page__highlight-empty">
                                        Nenhuma movimentação registrada.
                                    </p>
                                </button>
                            </div>
                        </section>
                    </AppCard>

                    <AppCard>
                        <section class="folder-show-page__section" aria-labelledby="folder-general-title">
                            <h2 id="folder-general-title" class="folder-show-page__section-title">
                                Dados gerais
                            </h2>

                            <dl class="folder-show-page__grid">
                                <div class="folder-show-page__field folder-show-page__field--full">
                                    <dt class="folder-show-page__label">
                                        Nome
                                    </dt>

                                    <dd class="folder-show-page__value">
                                        {{ displayValue(folder.name) }}
                                    </dd>
                                </div>

                                <div class="folder-show-page__field">
                                    <dt class="folder-show-page__label">
                                        Número do processo
                                    </dt>

                                    <dd class="folder-show-page__value">
                                        {{
                                            displayValue(
                                                folder.process_number,
                                            )
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </AppCard>
                </template>

                <!--
                |--------------------------------------------------------------------------
                | Partes
                |--------------------------------------------------------------------------
                -->

                <AppCard v-if="activeSection === 'clients'">
                    <section class="folder-show-page__section" aria-labelledby="folder-parts-title">
                        <div class="folder-show-page__section-heading">
                            <div>
                                <h2 id="folder-parts-title" class="folder-show-page__section-title">
                                    Partes
                                </h2>

                                <p class="folder-show-page__section-description">
                                    Consulte os clientes vinculados à pasta e suas qualificações.
                                </p>
                            </div>
                        </div>

                        <AppTable :columns="partsColumns" :rows="foldersStore.folderClients"
                            empty-text="Nenhuma parte vinculada.">
                            <template #cell-client="{ row }">
                                <div class="folder-show-page__client">
                                    <strong>
                                        {{
                                            row.client?.name ??
                                            '—'
                                        }}
                                    </strong>

                                    <span class="folder-show-page__client-document">
                                        {{
                                            row.client?.document ??
                                            '—'
                                        }}
                                    </span>
                                </div>
                            </template>

                            <template #cell-qualification="{ row }">
                                {{
                                    row.qualification?.name ??
                                    '—'
                                }}
                            </template>
                        </AppTable>
                    </section>
                </AppCard>

                <!--
                |--------------------------------------------------------------------------
                | Módulos operacionais
                |--------------------------------------------------------------------------
                -->

                <AppCard v-if="activeSection === 'documents'">
                    <FolderDocuments :folder-id="folder.id" @changed="refreshFolderSummary" />
                </AppCard>

                <AppCard v-if="activeSection === 'movements'">
                    <FolderMovements :folder-id="folder.id" @changed="refreshFolderSummary" />
                </AppCard>

                <AppCard v-if="activeSection === 'deadlines'">
                    <FolderDeadlines :folder-id="folder.id" @changed="refreshFolderSummary" />
                </AppCard>

                <AppCard v-if="activeSection === 'events'">
                    <FolderEvents :folder-id="folder.id" @changed="refreshFolderSummary" />
                </AppCard>

                <AppCard v-if="activeSection === 'tasks'">
                    <FolderTasks :folder-id="folder.id" @changed="refreshFolderSummary" />
                </AppCard>
            </template>
        </div>
    </PageContainer>
</template>

<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'

import {
    AppButton,
    AppCard,
    AppTable,
    AppTabs,
} from '@/components/ui'

import {
    formatShortDate,
    formatShortDateTime,
} from '@/utils/date'

import {
    folderEventTypeLabel,
    folderPriorityLabel,
} from '@/constants/folder'

import FolderDeadlines from '@/views/folders/components/FolderDeadlines.vue'
import FolderDocuments from '@/views/folders/components/FolderDocuments.vue'
import FolderEvents from '@/views/folders/components/FolderEvents.vue'
import FolderMovements from '@/views/folders/components/FolderMovements.vue'
import FolderTasks from '@/views/folders/components/FolderTasks.vue'

import {
    useAuthStore,
} from '@/stores/auth.js'

import {
    useFoldersStore,
} from '@/stores/folders.js'

const route =
    useRoute()

const router =
    useRouter()

const authStore =
    useAuthStore()

const foldersStore =
    useFoldersStore()

const loadError =
    ref('')

const activeSection =
    ref('overview')

const sections = [
    {
        value: 'overview',
        label: 'Visão geral',
    },

    {
        value: 'clients',
        label: 'Partes',
    },

    {
        value: 'documents',
        label: 'Documentos',
    },

    {
        value: 'movements',
        label: 'Movimentações',
    },

    {
        value: 'deadlines',
        label: 'Prazos',
    },

    {
        value: 'events',
        label: 'Agenda',
    },

    {
        value: 'tasks',
        label: 'Tarefas',
    },
]

const folderId =
    computed(() =>
        Number(
            route.params.id,
        ),
    )

const folder =
    computed(() =>
        foldersStore.folder,
    )

const summary =
    computed(() => ({
        documents_count:
            Number(
                folder.value?.summary?.documents_count ??
                0,
            ),

        pending_tasks_count:
            Number(
                folder.value?.summary?.pending_tasks_count ??
                0,
            ),

        pending_deadlines_count:
            Number(
                folder.value?.summary?.pending_deadlines_count ??
                0,
            ),

        attention: {
            deadlines:
                Array.isArray(
                    folder.value?.summary?.attention?.deadlines,
                )
                    ? folder.value.summary.attention.deadlines
                    : [],

            tasks:
                Array.isArray(
                    folder.value?.summary?.attention?.tasks,
                )
                    ? folder.value.summary.attention.tasks
                    : [],
        },

        next_event:
            folder.value?.summary?.next_event ??
            null,

        latest_movement:
            folder.value?.summary?.latest_movement ??
            null,
    }))

const canUpdate =
    computed(() =>
        authStore.hasPermission(
            'folders.update',
        ),
    )

const partsColumns = [
    {
        key: 'client',
        label: 'Cliente',
    },

    {
        key: 'qualification',
        label: 'Qualificação',
    },
]

function displayValue(value) {
    if (
        value === null ||
        value === undefined ||
        value === ''
    ) {
        return '—'
    }

    return value
}

function urgencyLabel(urgency) {
    const labels = {
        overdue:
            'Vencido',

        today:
            'Hoje',

        upcoming:
            'Próximo',

        unscheduled:
            'Sem vencimento',
    }

    return labels[urgency] ??
        '—'
}

function goBack() {
    return router.push({
        name: 'folders',
    })
}

function goToEdit() {
    if (!folder.value?.id) {
        return
    }

    return router.push({
        name: 'folders.edit',

        params: {
            id:
                folder.value.id,
        },
    })
}

async function loadFolder() {
    loadError.value =
        ''

    try {
        await foldersStore.fetchFolder(
            folderId.value,
        )
    } catch {
        loadError.value =
            'Não foi possível carregar a pasta. Tente novamente.'
    }
}

async function refreshFolderSummary() {
    try {
        await foldersStore.fetchFolder(
            folderId.value,
        )
    } catch {
        /*
         * A alteração da Agenda já foi concluída com sucesso.
         * Uma eventual falha ao atualizar o resumo não deve
         * substituir o estado da operação recém-realizada.
         */
    }
}

onMounted(
    loadFolder,
)
</script>

<style scoped>
.folder-show-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.folder-show-page__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-show-page__heading {
    min-width: 0;
}

.folder-show-page__summary-item,
.folder-show-page__highlight {
    width: 100%;
    font: inherit;
    color: inherit;
    text-align: left;
    cursor: pointer;
}

.folder-show-page__summary-item:focus-visible,
.folder-show-page__highlight:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}

.folder-show-page__title {
    margin: 0;

    color:
        var(--color-text);
}

.folder-show-page__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.folder-show-page__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}

.folder-show-page__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-show-page__section-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-show-page__section-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-lg);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__section-description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

/* ==========================================================================
   RESUMO OPERACIONAL
   ========================================================================== */

.folder-show-page__summary-grid {
    display: grid;

    grid-template-columns:
        repeat(3,
            minmax(0, 1fr));

    border-top:
        1px solid var(--color-divider);

    border-bottom:
        1px solid var(--color-divider);
}

.folder-show-page__summary-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);

    min-width: 0;

    padding:
        var(--space-4);

    border: 0;

    border-right:
        1px solid var(--color-divider);

    border-radius: 0;

    background:
        transparent;

    transition:
        background var(--duration-fast) var(--ease-standard);
}

.folder-show-page__summary-item:last-child {
    border-right: 0;
}

.folder-show-page__summary-item:hover {
    background:
        var(--color-surface-muted);
}

.folder-show-page__summary-label {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__summary-value {
    flex: 0 0 auto;

    color:
        var(--color-brand-secondary-active);

    font-size:
        var(--font-size-xl);

    font-weight:
        var(--font-weight-bold);

    line-height: 1;
}

/* ==========================================================================
   ATENÇÃO JURÍDICA
   ========================================================================== */

.folder-show-page__attention {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);

    padding-top:
        var(--space-2);
}

.folder-show-page__attention-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-show-page__attention-title {
    margin: 0;

    color:
        var(--color-brand);

    font-size:
        var(--font-size-md);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__attention-description {
    margin:
        var(--space-1) 0 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-show-page__attention-grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        0 var(--space-8);

    border-top:
        1px solid var(--color-divider);
}

.folder-show-page__attention-group {
    min-width: 0;

    padding-top:
        var(--space-4);
}

.folder-show-page__attention-group+.folder-show-page__attention-group {
    padding-left:
        var(--space-8);

    border-left:
        1px solid var(--color-divider);
}

.folder-show-page__attention-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);

    margin-bottom:
        var(--space-3);
}

.folder-show-page__attention-group-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__attention-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);

    padding: 0;

    border: 0;

    background:
        transparent;

    color:
        var(--color-brand-secondary);

    font: inherit;

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);

    cursor:
        pointer;
}

.folder-show-page__attention-link:hover {
    color:
        var(--color-brand-secondary-active);
}

.folder-show-page__attention-link:focus-visible {
    outline: none;

    border-radius:
        var(--radius-xs);

    box-shadow:
        var(--focus-ring);
}

.folder-show-page__attention-list {
    display: flex;
    flex-direction: column;
}

.folder-show-page__attention-item {
    display: grid;

    grid-template-columns:
        auto minmax(0, 1fr) auto;

    align-items: center;

    gap:
        var(--space-3);

    min-width: 0;

    padding:
        var(--space-3) 0;

    border-bottom:
        1px solid var(--color-divider);
}

.folder-show-page__attention-item:last-child {
    border-bottom: 0;
}

.folder-show-page__attention-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-height:
        1.5rem;

    padding:
        0 var(--space-2);

    border-radius:
        var(--radius-pill);

    font-size:
        var(--font-size-xs);

    font-weight:
        var(--font-weight-semibold);

    white-space:
        nowrap;
}

.folder-show-page__attention-status--overdue {
    background:
        var(--color-danger-soft);

    color:
        var(--color-danger);
}

.folder-show-page__attention-status--today {
    background:
        var(--color-surface-highlight-soft);

    color:
        var(--color-highlight-hover);
}

.folder-show-page__attention-status--upcoming {
    background:
        var(--color-surface-secondary-soft);

    color:
        var(--color-brand-secondary-active);
}

.folder-show-page__attention-status--unscheduled {
    background:
        var(--color-surface-muted);

    color:
        var(--color-text-muted);
}

.folder-show-page__attention-content {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}

.folder-show-page__attention-item-title {
    overflow: hidden;

    color:
        var(--color-text);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-medium);

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.folder-show-page__attention-priority {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-xs);
}

.folder-show-page__attention-date {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-xs);

    white-space:
        nowrap;
}

.folder-show-page__attention-empty {
    margin:
        var(--space-3) 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

/* ==========================================================================
   DESTAQUES
   ========================================================================== */

.folder-show-page__highlights-grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-4);
}

.folder-show-page__highlight {
    position: relative;

    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-3);

    overflow: hidden;

    padding:
        var(--space-4);

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);

    background:
        var(--color-surface);
}

.folder-show-page__highlight--event {
    background:
        color-mix(in srgb,
            var(--color-surface-highlight-soft) 36%,
            var(--color-surface));
}

.folder-show-page__highlight--movement {
    background:
        color-mix(in srgb,
            var(--color-surface-secondary-soft) 36%,
            var(--color-surface));
}

.folder-show-page__highlight-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-2);
}

.folder-show-page__highlight-label {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__highlight-type {
    display: inline-flex;
    align-items: center;

    min-height:
        1.5rem;

    padding:
        0 var(--space-2);

    border-radius:
        var(--radius-pill);

    background:
        var(--color-surface-highlight-soft);

    color:
        var(--color-highlight-hover);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__highlight-title {
    color:
        var(--color-brand);

    font-size:
        var(--font-size-md);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__highlight-meta {
    display: flex;
    flex-wrap: wrap;

    gap:
        var(--space-2) var(--space-4);

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-show-page__highlight-description {
    margin: 0;

    color:
        var(--color-text);

    line-height:
        1.5;
}

.folder-show-page__highlight-empty {
    margin: 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

/* ==========================================================================
   DADOS GERAIS
   ========================================================================== */

.folder-show-page__grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-5) var(--space-6);

    margin: 0;
}

.folder-show-page__field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}

.folder-show-page__field--full {
    grid-column:
        1 / -1;
}

.folder-show-page__label {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__value {
    min-height:
        1.5rem;

    margin: 0;

    overflow-wrap:
        anywhere;

    color:
        var(--color-text);
}

/* ==========================================================================
   PARTES
   ========================================================================== */

.folder-show-page__client {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.folder-show-page__client-document {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

/* ==========================================================================
   ERRO
   ========================================================================== */

.folder-show-page__error {
    padding:
        var(--space-3) var(--space-4);

    border:
        1px solid var(--color-danger);

    border-radius:
        var(--radius-md);

    background:
        var(--color-danger-soft);

    color:
        var(--color-danger);

    font-size:
        var(--font-size-sm);
}

/* ==========================================================================
   RESPONSIVIDADE
   ========================================================================== */

@media (max-width: 900px) {
    .folder-show-page__summary-grid {
        grid-template-columns:
            1fr;
    }

    .folder-show-page__highlights-grid {
        grid-template-columns:
            1fr;
    }
}

@media (max-width: 760px) {
    .folder-show-page__header {
        flex-direction:
            column;
    }

    .folder-show-page__actions {
        width:
            100%;

        justify-content:
            flex-start;
    }

    .folder-show-page__grid {
        grid-template-columns:
            1fr;
    }

    .folder-show-page__field--full {
        grid-column:
            auto;
    }
}
</style>