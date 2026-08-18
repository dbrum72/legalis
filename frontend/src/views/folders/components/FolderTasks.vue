<template>
    <section class="folder-tasks">
        <header class="folder-tasks__header">
            <div>
                <h2 class="folder-tasks__title">
                    Tarefas
                </h2>

                <p class="folder-tasks__description">
                    Acompanhe as tarefas vinculadas à pasta.
                </p>
            </div>

            <AppButton v-if="canUpdate && !isCreating" type="button" variant="outline" @click="openCreateForm">
                Nova tarefa
            </AppButton>
        </header>

        <div v-if="loadError" class="folder-tasks__error" role="alert">
            {{ loadError }}
        </div>

        <div v-if="completeError" class="folder-tasks__error" role="alert">
            {{ completeError }}
        </div>

        <div v-if="deleteError" class="folder-tasks__error" role="alert">
            {{ deleteError }}
        </div>

        <form v-if="isCreating" class="folder-tasks__form" @submit.prevent="submitCreate">
            <div class="folder-tasks__form-header">
                <div>
                    <h3 class="folder-tasks__form-title">
                        Nova tarefa
                    </h3>

                    <p class="folder-tasks__form-description">
                        Registre uma atividade interna vinculada a esta pasta.
                    </p>
                </div>
            </div>

            <div class="folder-tasks__form-grid">
                <div class="folder-tasks__field folder-tasks__field--wide">
                    <label class="folder-tasks__label" for="folder-task-title">
                        Título *
                    </label>

                    <input id="folder-task-title" v-model="form.title" name="title" type="text" maxlength="180"
                        class="folder-tasks__control" :disabled="isSubmitting">
                </div>

                <div class="folder-tasks__field">
                    <label class="folder-tasks__label" for="folder-task-priority">
                        Prioridade
                    </label>

                    <select id="folder-task-priority" v-model="form.priority" name="priority"
                        class="folder-tasks__control" :disabled="isSubmitting">
                        <option value="low">
                            Baixa
                        </option>

                        <option value="medium">
                            Média
                        </option>

                        <option value="high">
                            Alta
                        </option>
                    </select>
                </div>

                <div class="folder-tasks__field">
                    <label class="folder-tasks__label" for="folder-task-due-at">
                        Vencimento
                    </label>

                    <input id="folder-task-due-at" v-model="form.due_at" name="due_at" type="datetime-local"
                        class="folder-tasks__control" :disabled="isSubmitting">
                </div>

                <div class="folder-tasks__field folder-tasks__field--full">
                    <label class="folder-tasks__label" for="folder-task-description">
                        Descrição
                    </label>

                    <textarea id="folder-task-description" v-model="form.description" name="description" rows="4"
                        maxlength="10000" class="folder-tasks__control folder-tasks__textarea"
                        :disabled="isSubmitting" />
                </div>
            </div>

            <div v-if="createError" class="folder-tasks__error" role="alert">
                {{ createError }}
            </div>

            <div class="folder-tasks__form-actions">
                <AppButton type="button" variant="outline" :disabled="isSubmitting" @click="cancelCreate">
                    Cancelar
                </AppButton>

                <AppButton type="submit" :disabled="isSubmitting">
                    Salvar tarefa
                </AppButton>
            </div>
        </form>

        <div v-if="
            !loadError &&
            folderTasksStore.tasks.length === 0
        " class="folder-tasks__empty">
            Nenhuma tarefa registrada.
        </div>

        <div v-else-if="!loadError" class="folder-tasks__list">
            <article v-for="task in folderTasksStore.tasks" :key="task.id" class="folder-tasks__item">
                <div class="folder-tasks__item-header">
                    <div class="folder-tasks__item-heading">
                        <h3 class="folder-tasks__item-title">
                            {{ task.title }}
                        </h3>

                        <div class="folder-tasks__badges">
                            <span class="folder-tasks__badge" :class="`folder-tasks__badge--priority-${task.priority}`">
                                {{ priorityLabel(task.priority) }}
                            </span>

                            <span class="folder-tasks__badge" :class="`folder-tasks__badge--${task.status}`">
                                {{ statusLabel(task.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <p v-if="task.description" class="folder-tasks__item-description">
                    {{ task.description }}
                </p>

                <div class="folder-tasks__meta">
                    <span>
                        Responsável:
                        {{ task.user?.name ?? '—' }}
                    </span>

                    <span v-if="task.due_at">
                        Vencimento:
                        {{ displayDateTime(task.due_at) }}
                    </span>

                    <span v-if="task.completed_at">
                        Concluído em:
                        {{ displayDateTime(task.completed_at) }}
                    </span>
                </div>

                <div v-if="canUpdate" class="folder-tasks__actions">
                    <AppButton v-if="task.status === 'pending'" type="button" size="sm" variant="outline" :disabled="completingId !== null ||
                        deleting
                        " @click="completeTask(task)">
                        Concluir
                    </AppButton>

                    <AppButton type="button" size="sm" variant="ghost" :disabled="deleting ||
                        completingId !== null
                        " @click="requestDelete(task)">
                        Excluir
                    </AppButton>
                </div>
            </article>
        </div>

        <AppConfirmDialog :open="Boolean(taskToDelete)" title="Excluir tarefa" :message="deleteMessage"
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
    useFolderTasksStore,
} from '@/stores/folder-tasks.js'

const props = defineProps({
    folderId: {
        type: [
            Number,
            String,
        ],

        required: true,
    },
})

const authStore =
    useAuthStore()

const folderTasksStore =
    useFolderTasksStore()

const loadError =
    ref('')

const createError =
    ref('')

const completeError =
    ref('')

const deleteError =
    ref('')

const isCreating =
    ref(false)

const isSubmitting =
    ref(false)

const completingId =
    ref(null)

const deleting =
    ref(false)

const taskToDelete =
    ref(null)

const form =
    reactive({
        title: '',
        description: '',
        priority: 'medium',
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
        if (!taskToDelete.value) {
            return ''
        }

        return `Deseja realmente excluir a tarefa "${taskToDelete.value.title}"?`
    })

function resetForm() {
    form.title =
        ''

    form.description =
        ''

    form.priority =
        'medium'

    form.due_at =
        ''

    createError.value =
        ''
}

function openCreateForm() {
    resetForm()

    isCreating.value =
        true
}

function cancelCreate() {
    if (isSubmitting.value) {
        return
    }

    resetForm()

    isCreating.value =
        false
}

function priorityLabel(priority) {
    const labels = {
        low:
            'Baixa',

        medium:
            'Média',

        high:
            'Alta',
    }

    return labels[priority] ??
        priority ??
        '—'
}

function statusLabel(status) {
    const labels = {
        pending:
            'Pendente',

        completed:
            'Concluído',
    }

    return labels[status] ??
        status ??
        '—'
}

function displayDateTime(value) {
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
            dateStyle:
                'short',

            timeStyle:
                'short',
        },
    ).format(date)
}

async function loadTasks() {
    loadError.value =
        ''

    try {
        await folderTasksStore.fetchTasks(
            props.folderId,
        )
    } catch {
        loadError.value =
            'Não foi possível carregar as tarefas. Tente novamente.'
    }
}

async function submitCreate() {
    createError.value =
        ''

    const title =
        form.title.trim()

    if (!title) {
        return
    }

    const description =
        form.description.trim()

    const dueAt =
        form.due_at.trim()

    const payload = {
        title,

        description:
            description ||
            null,

        priority:
            form.priority,

        due_at:
            dueAt ||
            null,
    }

    isSubmitting.value =
        true

    try {
        await folderTasksStore.createTask(
            props.folderId,
            payload,
        )

        resetForm()

        isCreating.value =
            false
    } catch {
        createError.value =
            'Não foi possível criar a tarefa. Tente novamente.'
    } finally {
        isSubmitting.value =
            false
    }
}

async function completeTask(
    task,
) {
    if (
        completingId.value !== null ||
        task.status !== 'pending'
    ) {
        return
    }

    completingId.value =
        task.id

    completeError.value =
        ''

    try {
        await folderTasksStore.completeTask(
            props.folderId,
            task.id,
        )
    } catch {
        completeError.value =
            'Não foi possível concluir a tarefa. Tente novamente.'
    } finally {
        completingId.value =
            null
    }
}

function requestDelete(
    task,
) {
    taskToDelete.value =
        task

    deleteError.value =
        ''
}

function cancelDelete() {
    if (deleting.value) {
        return
    }

    taskToDelete.value =
        null

    deleteError.value =
        ''
}

async function confirmDelete() {
    if (
        !taskToDelete.value ||
        deleting.value
    ) {
        return
    }

    deleting.value =
        true

    deleteError.value =
        ''

    try {
        await folderTasksStore.removeTask(
            props.folderId,
            taskToDelete.value.id,
        )

        taskToDelete.value =
            null
    } catch {
        deleteError.value =
            'Não foi possível excluir a tarefa. Tente novamente.'
    } finally {
        deleting.value =
            false
    }
}

onMounted(
    loadTasks,
)
</script>

<style scoped>
.folder-tasks {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-tasks__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-tasks__title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-lg);

    font-weight:
        var(--font-weight-semibold);
}

.folder-tasks__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.folder-tasks__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);

    padding:
        var(--space-5);

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);

    background:
        var(--color-surface);
}

.folder-tasks__form-header {
    display: flex;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-tasks__form-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-md);

    font-weight:
        var(--font-weight-semibold);
}

.folder-tasks__form-description {
    margin:
        var(--space-1) 0 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-tasks__form-grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-4);
}

.folder-tasks__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.folder-tasks__field--wide {
    min-width: 0;
}

.folder-tasks__field--full {
    grid-column:
        1 / -1;
}

.folder-tasks__label {
    color:
        var(--color-text);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-tasks__control {
    width: 100%;

    min-height:
        2.75rem;

    box-sizing:
        border-box;

    padding:
        var(--space-2) var(--space-3);

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);

    background:
        var(--color-surface);

    color:
        var(--color-text);

    font:
        inherit;
}

.folder-tasks__control:focus {
    outline:
        2px solid var(--color-primary);

    outline-offset:
        1px;
}

.folder-tasks__control:disabled {
    cursor:
        not-allowed;

    opacity:
        0.65;
}

.folder-tasks__textarea {
    min-height:
        6rem;

    resize:
        vertical;
}

.folder-tasks__form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
}

.folder-tasks__list {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.folder-tasks__item {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);

    padding:
        var(--space-4);

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);
}

.folder-tasks__item-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-tasks__item-heading {
    min-width: 0;
}

.folder-tasks__item-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-md);

    font-weight:
        var(--font-weight-semibold);
}

.folder-tasks__badges {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);

    margin-top:
        var(--space-2);
}

.folder-tasks__badge {
    display: inline-flex;
    align-items: center;

    min-height:
        1.5rem;

    padding:
        0 var(--space-2);

    border-radius:
        999px;

    background:
        var(--color-surface-muted);

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-tasks__badge--priority-high {
    color:
        var(--color-danger);
}

.folder-tasks__badge--priority-medium {
    color:
        var(--color-warning);
}

.folder-tasks__badge--priority-low {
    color:
        var(--color-success);
}

.folder-tasks__badge--pending {
    color:
        var(--color-primary);
}

.folder-tasks__badge--completed {
    color:
        var(--color-success);
}

.folder-tasks__item-description {
    margin: 0;

    color:
        var(--color-text);

    line-height:
        1.5;
}

.folder-tasks__meta {
    display: flex;
    flex-wrap: wrap;

    gap:
        var(--space-2) var(--space-4);

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-tasks__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}

.folder-tasks__empty {
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

.folder-tasks__error {
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

    .folder-tasks__header,
    .folder-tasks__item-header {
        flex-direction:
            column;
    }

    .folder-tasks__form-grid {
        grid-template-columns:
            1fr;
    }

    .folder-tasks__field--full {
        grid-column:
            auto;
    }

    .folder-tasks__form-actions {
        flex-direction:
            column-reverse;
    }

    .folder-tasks__actions {
        justify-content:
            flex-start;
    }
}
</style>