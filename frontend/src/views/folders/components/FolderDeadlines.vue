<template>
    <section class="folder-deadlines">
        <header class="folder-deadlines__header">
            <div>
                <h2 class="folder-deadlines__title">
                    Prazos
                </h2>

                <p class="folder-deadlines__description">
                    Acompanhe os prazos vinculados à pasta.
                </p>
            </div>

            <AppButton v-if="canUpdate && !showCreateForm" type="button" variant="outline" @click="openCreateForm">
                Novo prazo
            </AppButton>
        </header>

        <form v-if="showCreateForm" class="folder-deadlines__form" @submit.prevent="submitDeadline">
            <div class="folder-deadlines__form-grid">
                <div class="folder-deadlines__field">
                    <label class="folder-deadlines__label" for="folder-deadline-title">
                        Título
                    </label>

                    <input id="folder-deadline-title" v-model="form.title" class="folder-deadlines__input" name="title"
                        type="text" maxlength="180" :disabled="submitting" required>
                </div>

                <div class="folder-deadlines__field">
                    <label class="folder-deadlines__label" for="folder-deadline-due-at">
                        Vencimento
                    </label>

                    <input id="folder-deadline-due-at" v-model="form.due_at" class="folder-deadlines__input"
                        name="due_at" type="datetime-local" :disabled="submitting" required>
                </div>

                <div class="folder-deadlines__field folder-deadlines__field--full">
                    <label class="folder-deadlines__label" for="folder-deadline-description">
                        Descrição
                    </label>

                    <textarea id="folder-deadline-description" v-model="form.description"
                        class="folder-deadlines__textarea" name="description" rows="4" maxlength="10000"
                        :disabled="submitting" />
                </div>
            </div>

            <div v-if="createError" class="folder-deadlines__error" role="alert">
                {{ createError }}
            </div>

            <footer class="folder-deadlines__form-actions">
                <AppButton type="button" variant="ghost" :disabled="submitting" @click="cancelCreate">
                    Cancelar
                </AppButton>

                <AppButton type="submit" variant="primary" :loading="submitting" :disabled="submitting">
                    Criar prazo
                </AppButton>
            </footer>
        </form>

        <div v-if="loadError" class="folder-deadlines__error" role="alert">
            {{ loadError }}
        </div>

        <div v-if="completeError" class="folder-deadlines__error" role="alert">
            {{ completeError }}
        </div>

        <div v-if="deleteError" class="folder-deadlines__error" role="alert">
            {{ deleteError }}
        </div>

        <div v-if="
            !loadError &&
            folderDeadlinesStore.deadlines.length === 0
        " class="folder-deadlines__empty">
            Nenhum prazo registrado.
        </div>

        <div v-else-if="
            !loadError &&
            folderDeadlinesStore.deadlines.length > 0
        " class="folder-deadlines__list">
            <article v-for="deadline in folderDeadlinesStore.deadlines" :key="deadline.id"
                class="folder-deadlines__item">
                <div class="folder-deadlines__item-header">
                    <div class="folder-deadlines__item-heading">
                        <h3 class="folder-deadlines__item-title">
                            {{ deadline.title }}
                        </h3>

                        <span class="folder-deadlines__status" :class="statusClass(deadline.status)">
                            {{ statusLabel(deadline.status) }}
                        </span>
                    </div>

                    <time class="folder-deadlines__due-date" :datetime="deadline.due_at">
                        {{ displayDate(deadline.due_at) }}
                    </time>
                </div>

                <p v-if="deadline.description" class="folder-deadlines__item-description">
                    {{ deadline.description }}
                </p>

                <div class="folder-deadlines__meta">
                    <span>
                        Responsável:
                        {{ deadline.user?.name ?? '—' }}
                    </span>

                    <span v-if="
                        deadline.status === 'completed' &&
                        deadline.completed_at
                    ">
                        Concluído em:
                        {{ displayDate(deadline.completed_at) }}
                    </span>
                </div>

                <div v-if="canUpdate" class="folder-deadlines__actions">
                    <AppButton v-if="deadline.status === 'pending'" type="button" size="sm" variant="outline" :loading="completingId === deadline.id
                        " :disabled="completingId !== null ||
                            deleting
                            " @click="
                                completeDeadline(deadline)
                                ">
                        Concluir
                    </AppButton>

                    <AppButton type="button" size="sm" variant="ghost" :disabled="deleting ||
                        completingId !== null
                        " @click="
                            requestDelete(deadline)
                            ">
                        Excluir
                    </AppButton>
                </div>
            </article>
        </div>

        <AppConfirmDialog :open="Boolean(deadlineToDelete)" title="Excluir prazo" :message="deleteMessage"
            confirm-label="Excluir" cancel-label="Cancelar" :loading="deleting" @confirm="confirmDelete"
            @cancel="cancelDelete" />
    </section>
</template>

<script setup>
import {
    computed,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    AppButton,
    AppConfirmDialog,
} from '@/components/ui'

import {
    useAuthStore,
} from '@/stores/auth.js'

import {
    useFolderDeadlinesStore,
} from '@/stores/folder-deadlines.js'

const props = defineProps({
    folderId: {
        type: [
            Number,
            String,
        ],

        required: true,
    },
})

const emit = defineEmits([
    'changed',
])

const authStore =
    useAuthStore()

const folderDeadlinesStore =
    useFolderDeadlinesStore()

const loadError =
    ref('')

const createError =
    ref('')

const completeError =
    ref('')

const deleteError =
    ref('')

const showCreateForm =
    ref(false)

const submitting =
    ref(false)

const completingId =
    ref(null)

const deleting =
    ref(false)

const deadlineToDelete =
    ref(null)

const form =
    reactive({
        title: '',
        description: '',
        due_at: '',
    })

const canUpdate =
    computed(() =>
        authStore.hasPermission(
            'folders.update',
        ),
    )

const deleteMessage =
    computed(() => {
        if (!deadlineToDelete.value) {
            return ''
        }

        return `Deseja realmente excluir o prazo "${deadlineToDelete.value.title}"?`
    })

function statusLabel(status) {
    const labels = {
        pending:
            'Pendente',

        completed:
            'Concluído',

        cancelled:
            'Cancelado',
    }

    return labels[status] ??
        status ??
        '—'
}

function statusClass(status) {
    return {
        'folder-deadlines__status--pending':
            status === 'pending',

        'folder-deadlines__status--completed':
            status === 'completed',

        'folder-deadlines__status--cancelled':
            status === 'cancelled',
    }
}

function displayDate(value) {
    if (!value) {
        return '—'
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return value
    }

    return new Intl.DateTimeFormat(
        'pt-BR',
        {
            dateStyle: 'short',
            timeStyle: 'short',
        },
    ).format(date)
}

function openCreateForm() {
    createError.value =
        ''

    showCreateForm.value =
        true
}

function resetForm() {
    form.title =
        ''

    form.description =
        ''

    form.due_at =
        ''

    createError.value =
        ''
}

function cancelCreate() {
    if (submitting.value) {
        return
    }

    resetForm()

    showCreateForm.value =
        false
}

async function submitDeadline() {
    if (
        submitting.value ||
        !form.title.trim() ||
        !form.due_at
    ) {
        return
    }

    submitting.value =
        true

    createError.value =
        ''

    const payload = {
        title:
            form.title.trim(),

        description:
            form.description.trim(),

        due_at:
            new Date(
                form.due_at,
            ).toISOString(),
    }

    try {
        await folderDeadlinesStore.createDeadline(
            props.folderId,
            payload,
        )

        emit(
            'changed',
        )

        resetForm()

        showCreateForm.value =
            false
    } catch {
        createError.value =
            'Não foi possível criar o prazo. Tente novamente.'
    } finally {
        submitting.value =
            false
    }
}

async function completeDeadline(
    deadline,
) {
    if (
        completingId.value !== null ||
        deadline.status !== 'pending'
    ) {
        return
    }

    completingId.value =
        deadline.id

    completeError.value =
        ''

    try {
        await folderDeadlinesStore.completeDeadline(
            props.folderId,
            deadline.id,
        )
        emit(
            'changed',
        )
    } catch {
        completeError.value =
            'Não foi possível concluir o prazo. Tente novamente.'
    } finally {
        completingId.value =
            null
    }
}

function requestDelete(
    deadline,
) {
    deadlineToDelete.value =
        deadline

    deleteError.value =
        ''
}

function cancelDelete() {
    if (deleting.value) {
        return
    }

    deadlineToDelete.value =
        null

    deleteError.value =
        ''
}

async function confirmDelete() {
    if (
        !deadlineToDelete.value ||
        deleting.value
    ) {
        return
    }

    deleting.value =
        true

    deleteError.value =
        ''

    try {
        await folderDeadlinesStore.removeDeadline(
            props.folderId,
            deadlineToDelete.value.id,
        )
        emit(
            'changed',
        )

        deadlineToDelete.value =
            null
    } catch {
        deleteError.value =
            'Não foi possível excluir o prazo. Tente novamente.'
    } finally {
        deleting.value =
            false
    }
}

async function loadDeadlines() {
    loadError.value =
        ''

    try {
        await folderDeadlinesStore.fetchDeadlines(
            props.folderId,
        )
    } catch {
        loadError.value =
            'Não foi possível carregar os prazos. Tente novamente.'
    }
}

onMounted(
    loadDeadlines,
)
</script>

<style scoped>
.folder-deadlines {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-deadlines__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-deadlines__title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-lg);

    font-weight:
        var(--font-weight-semibold);
}

.folder-deadlines__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.folder-deadlines__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);

    padding:
        var(--space-5);

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);
}

.folder-deadlines__form-grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-4) var(--space-5);
}

.folder-deadlines__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.folder-deadlines__field--full {
    grid-column:
        1 / -1;
}

.folder-deadlines__label {
    color:
        var(--color-text);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-deadlines__input,
.folder-deadlines__textarea {
    width: 100%;

    box-sizing:
        border-box;

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);

    background:
        var(--color-surface);

    color:
        var(--color-text);

    font: inherit;
}

.folder-deadlines__input {
    min-height:
        2.75rem;

    padding:
        var(--space-2) var(--space-3);
}

.folder-deadlines__textarea {
    min-height:
        7rem;

    padding:
        var(--space-3);

    resize:
        vertical;
}

.folder-deadlines__form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
}

.folder-deadlines__list {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.folder-deadlines__item {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);

    padding:
        var(--space-4);

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);

    background:
        var(--color-surface);
}

.folder-deadlines__item-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-deadlines__item-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
}

.folder-deadlines__item-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-md);

    font-weight:
        var(--font-weight-semibold);
}

.folder-deadlines__status {
    display: inline-flex;
    align-items: center;

    min-height:
        1.5rem;

    padding:
        0 var(--space-2);

    border-radius:
        999px;

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-deadlines__status--pending {
    background:
        var(--color-warning-soft);

    color:
        var(--color-warning);
}

.folder-deadlines__status--completed {
    background:
        var(--color-success-soft);

    color:
        var(--color-success);
}

.folder-deadlines__status--cancelled {
    background:
        var(--color-surface-muted);

    color:
        var(--color-text-muted);
}

.folder-deadlines__due-date {
    flex-shrink: 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-deadlines__item-description {
    margin: 0;

    color:
        var(--color-text);

    line-height: 1.5;
}

.folder-deadlines__meta {
    display: flex;
    flex-wrap: wrap;

    gap:
        var(--space-2) var(--space-4);

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-deadlines__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}

.folder-deadlines__empty {
    padding:
        var(--space-6);

    border:
        1px dashed var(--color-divider);

    border-radius:
        var(--radius-md);

    color:
        var(--color-text-muted);

    text-align:
        center;
}

.folder-deadlines__error {
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

@media (max-width: 640px) {

    .folder-deadlines__header,
    .folder-deadlines__item-header {
        flex-direction:
            column;
    }

    .folder-deadlines__form-grid {
        grid-template-columns:
            1fr;
    }

    .folder-deadlines__field--full {
        grid-column:
            auto;
    }

    .folder-deadlines__form-actions {
        flex-wrap:
            wrap;
    }

    .folder-deadlines__due-date {
        flex-shrink:
            1;
    }

    .folder-deadlines__actions {
        justify-content:
            flex-start;
    }
}
</style>