<template>
    <section class="folder-events">
        <header class="folder-events__header">
            <div>
                <h2 class="folder-events__title">
                    Agenda
                </h2>

                <p class="folder-events__description">
                    Acompanhe os compromissos vinculados à pasta.
                </p>
            </div>

            <AppButton v-if="canUpdate && !isCreating" type="button" variant="outline" @click="openCreateForm">
                Novo compromisso
            </AppButton>
        </header>

        <div v-if="loadError" class="folder-events__error" role="alert">
            {{ loadError }}
        </div>

        <div v-if="completeError" class="folder-events__error" role="alert">
            {{ completeError }}
        </div>

        <div v-if="deleteError" class="folder-events__error" role="alert">
            {{ deleteError }}
        </div>

        <form v-if="isCreating" class="folder-events__form" @submit.prevent="submitCreate">
            <div class="folder-events__form-header">
                <div>
                    <h3 class="folder-events__form-title">
                        Novo compromisso
                    </h3>

                    <p class="folder-events__form-description">
                        Registre um compromisso vinculado a esta pasta.
                    </p>
                </div>
            </div>

            <div class="folder-events__form-grid">
                <div class="folder-events__field">
                    <label class="folder-events__label" for="folder-event-type">
                        Tipo *
                    </label>

                    <select id="folder-event-type" v-model="form.type" name="type" class="folder-events__control"
                        :disabled="isSubmitting">
                        <option value="">
                            Selecione
                        </option>

                        <option value="hearing">
                            Audiência
                        </option>

                        <option value="meeting">
                            Reunião
                        </option>

                        <option value="expert_exam">
                            Perícia
                        </option>

                        <option value="diligence">
                            Diligência
                        </option>

                        <option value="other">
                            Outro
                        </option>
                    </select>
                </div>

                <div class="folder-events__field folder-events__field--wide">
                    <label class="folder-events__label" for="folder-event-title">
                        Título *
                    </label>

                    <input id="folder-event-title" v-model="form.title" name="title" type="text" maxlength="180"
                        class="folder-events__control" :disabled="isSubmitting">
                </div>

                <div class="folder-events__field">
                    <label class="folder-events__label" for="folder-event-starts-at">
                        Início *
                    </label>

                    <input id="folder-event-starts-at" v-model="form.starts_at" name="starts_at" type="datetime-local"
                        class="folder-events__control" :disabled="isSubmitting">
                </div>

                <div class="folder-events__field">
                    <label class="folder-events__label" for="folder-event-ends-at">
                        Término
                    </label>

                    <input id="folder-event-ends-at" v-model="form.ends_at" name="ends_at" type="datetime-local"
                        class="folder-events__control" :disabled="isSubmitting">
                </div>

                <div class="folder-events__field folder-events__field--wide">
                    <label class="folder-events__label" for="folder-event-location">
                        Local
                    </label>

                    <input id="folder-event-location" v-model="form.location" name="location" type="text"
                        maxlength="255" class="folder-events__control" :disabled="isSubmitting">
                </div>

                <div class="folder-events__field folder-events__field--full">
                    <label class="folder-events__label" for="folder-event-description">
                        Descrição
                    </label>

                    <textarea id="folder-event-description" v-model="form.description" name="description" rows="4"
                        maxlength="10000" class="folder-events__control folder-events__textarea"
                        :disabled="isSubmitting" />
                </div>
            </div>

            <div v-if="createError" class="folder-events__error" role="alert">
                {{ createError }}
            </div>

            <div class="folder-events__form-actions">
                <AppButton type="button" variant="outline" :disabled="isSubmitting" @click="cancelCreate">
                    Cancelar
                </AppButton>

                <AppButton type="submit" :disabled="isSubmitting">
                    Salvar compromisso
                </AppButton>
            </div>
        </form>

        <div v-if="
            !loadError &&
            folderEventsStore.events.length === 0
        " class="folder-events__empty">
            Nenhum compromisso registrado.
        </div>

        <div v-else-if="!loadError" class="folder-events__list">
            <article v-for="event in folderEventsStore.events" :key="event.id" class="folder-events__item">
                <div class="folder-events__item-header">
                    <div class="folder-events__item-heading">
                        <h3 class="folder-events__item-title">
                            {{ event.title }}
                        </h3>

                        <div class="folder-events__badges">
                            <span class="folder-events__badge" :class="`folder-events__badge--${event.type}`">
                                {{ folderEventTypeLabel(event.type) }}
                            </span>

                            <span class="folder-events__badge" :class="`folder-events__badge--${event.status}`">
                                {{ folderEventStatusLabel(event.status) }}
                            </span>
                        </div>
                    </div>

                    <div class="folder-events__schedule">
                        <time class="folder-events__date" :datetime="event.starts_at">
                            {{ formatShortDateTime(event.starts_at) }}
                        </time>

                        <span v-if="event.ends_at" class="folder-events__end-date">
                            até {{ formatShortDateTime(event.ends_at) }}
                        </span>
                    </div>
                </div>

                <p v-if="event.description" class="folder-events__item-description">
                    {{ event.description }}
                </p>

                <div class="folder-events__meta">
                    <span>
                        Responsável:
                        {{ event.user?.name ?? '—' }}
                    </span>

                    <span v-if="event.location">
                        Local:
                        {{ event.location }}
                    </span>

                    <span v-if="event.completed_at">
                        Concluído em:
                        {{ formatShortDateTime(event.completed_at) }}
                    </span>
                </div>

                <div v-if="canUpdate" class="folder-events__actions">
                    <AppButton v-if="event.status === 'scheduled'" type="button" size="sm" variant="outline" :disabled="completingId !== null ||
                        deleting
                        " @click="completeEvent(event)">
                        Concluir
                    </AppButton>

                    <AppButton type="button" size="sm" variant="ghost" :disabled="deleting ||
                        completingId !== null
                        " @click="requestDelete(event)">
                        Excluir
                    </AppButton>
                </div>
            </article>
        </div>

        <AppConfirmDialog :open="Boolean(eventToDelete)" title="Excluir compromisso" :message="deleteMessage"
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
    formatShortDateTime,
} from '@/utils/date'

import {
    folderEventTypeLabel,
    folderEventStatusLabel,
} from '@/constants/folder'

import {
    useAuthStore,
} from '@/stores/auth.js'

import {
    useFolderEventsStore,
} from '@/stores/folder-events.js'

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

const folderEventsStore =
    useFolderEventsStore()

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

const eventToDelete =
    ref(null)

const form =
    reactive({
        type: '',
        title: '',
        description: '',
        starts_at: '',
        ends_at: '',
        location: '',
    })

const canUpdate =
    computed(() =>
        authStore.hasPermission(
            'folders.update',
        ),
    )

const deleteMessage =
    computed(() => {
        if (!eventToDelete.value) {
            return ''
        }

        return `Deseja realmente excluir o compromisso "${eventToDelete.value.title}"?`
    })

function resetForm() {
    form.type =
        ''

    form.title =
        ''

    form.description =
        ''

    form.starts_at =
        ''

    form.ends_at =
        ''

    form.location =
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

function localDateTimeToUtc(value) {
    if (!value) {
        return null
    }

    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return null
    }

    return date.toISOString()
}

async function loadEvents() {
    loadError.value =
        ''

    try {
        await folderEventsStore.fetchEvents(
            props.folderId,
        )
    } catch {
        loadError.value =
            'Não foi possível carregar os compromissos. Tente novamente.'
    }
}

async function submitCreate() {
    createError.value =
        ''

    const type =
        form.type.trim()

    const title =
        form.title.trim()

    const startsAt =
        form.starts_at.trim()

    if (
        !type ||
        !title ||
        !startsAt
    ) {
        return
    }

    const startsAtUtc =
        localDateTimeToUtc(
            startsAt,
        )

    const endsAtUtc =
        localDateTimeToUtc(
            form.ends_at.trim(),
        )

    if (!startsAtUtc) {
        return
    }

    const payload = {
        type,

        title,

        description:
            form.description.trim(),

        starts_at:
            startsAtUtc,

        ends_at:
            endsAtUtc,

        location:
            form.location.trim(),
    }

    isSubmitting.value =
        true

    try {
        await folderEventsStore.createEvent(
            props.folderId,
            payload,
        )

        resetForm()

        isCreating.value =
            false

        emit(
            'changed',
        )
    } catch {
        createError.value =
            'Não foi possível criar o compromisso. Tente novamente.'
    } finally {
        isSubmitting.value =
            false
    }
}

async function completeEvent(
    event,
) {
    if (
        completingId.value !== null ||
        event.status !== 'scheduled'
    ) {
        return
    }

    completingId.value =
        event.id

    completeError.value =
        ''

    try {
        await folderEventsStore.completeEvent(
            props.folderId,
            event.id,
        )

        emit(
            'changed',
        )
    } catch {
        completeError.value =
            'Não foi possível concluir o compromisso. Tente novamente.'
    } finally {
        completingId.value =
            null
    }
}

function requestDelete(
    event,
) {
    eventToDelete.value =
        event

    deleteError.value =
        ''
}

function cancelDelete() {
    if (deleting.value) {
        return
    }

    eventToDelete.value =
        null

    deleteError.value =
        ''
}

async function confirmDelete() {
    if (
        !eventToDelete.value ||
        deleting.value
    ) {
        return
    }

    deleting.value =
        true

    deleteError.value =
        ''

    try {
        await folderEventsStore.removeEvent(
            props.folderId,
            eventToDelete.value.id,
        )

        eventToDelete.value =
            null

        emit(
            'changed',
        )
    } catch {
        deleteError.value =
            'Não foi possível excluir o compromisso. Tente novamente.'
    } finally {
        deleting.value =
            false
    }
}

onMounted(
    loadEvents,
)
</script>

<style scoped>
.folder-events {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-events__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-events__title {
    margin: 0;
    color: var(--color-text);
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
}

.folder-events__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.folder-events__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
    padding: var(--space-5);

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);

    background:
        var(--color-surface);
}

.folder-events__form-header {
    display: flex;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-events__form-title {
    margin: 0;
    color: var(--color-text);
    font-size: var(--font-size-md);
    font-weight: var(--font-weight-semibold);
}

.folder-events__form-description {
    margin:
        var(--space-1) 0 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-events__form-grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-4);
}

.folder-events__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.folder-events__field--wide {
    min-width: 0;
}

.folder-events__field--full {
    grid-column:
        1 / -1;
}

.folder-events__label {
    color:
        var(--color-text);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-events__control {
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

.folder-events__control:focus {
    outline:
        2px solid var(--color-primary);

    outline-offset:
        1px;
}

.folder-events__control:disabled {
    cursor:
        not-allowed;

    opacity:
        0.65;
}

.folder-events__textarea {
    min-height:
        6rem;

    resize:
        vertical;
}

.folder-events__form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
}

.folder-events__list {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.folder-events__item {
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

.folder-events__item-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-events__item-heading {
    min-width: 0;
}

.folder-events__item-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-md);

    font-weight:
        var(--font-weight-semibold);
}

.folder-events__badges {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);

    margin-top:
        var(--space-2);
}

.folder-events__badge {
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

.folder-events__badge--scheduled {
    color:
        var(--color-primary);
}

.folder-events__badge--completed {
    color:
        var(--color-success);
}

.folder-events__badge--cancelled {
    color:
        var(--color-danger);
}

.folder-events__schedule {
    flex-shrink: 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);

    text-align:
        right;
}

.folder-events__date {
    color:
        var(--color-text);

    font-weight:
        var(--font-weight-semibold);
}

.folder-events__end-date {
    display: block;

    margin-top:
        var(--space-1);
}

.folder-events__item-description {
    margin: 0;

    color:
        var(--color-text);

    line-height:
        1.5;
}

.folder-events__meta {
    display: flex;
    flex-wrap: wrap;

    gap:
        var(--space-2) var(--space-4);

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-events__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}

.folder-events__empty {
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

.folder-events__error {
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

    .folder-events__header,
    .folder-events__item-header {
        flex-direction:
            column;
    }

    .folder-events__form-grid {
        grid-template-columns:
            1fr;
    }

    .folder-events__field--full {
        grid-column:
            auto;
    }

    .folder-events__schedule {
        text-align:
            left;
    }

    .folder-events__form-actions {
        flex-direction:
            column-reverse;
    }

    .folder-events__actions {
        justify-content:
            flex-start;
    }
}
</style>