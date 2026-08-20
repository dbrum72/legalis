<template>
    <section class="agenda-page">
        <header class="agenda-page__header">
            <div>
                <h1 class="agenda-page__title">
                    Agenda
                </h1>

                <p class="agenda-page__description">
                    Acompanhe tarefas, prazos e compromissos em uma visão de calendário.
                </p>
            </div>
        </header>

        <div v-if="errorMessage" class="agenda-page__alert" role="alert">
            {{ errorMessage }}
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Calendário
        |--------------------------------------------------------------------------
        -->

        <section class="agenda-calendar" aria-labelledby="agenda-calendar-title">
            <header class="agenda-calendar__toolbar">
                <div class="agenda-calendar__navigation">
                    <button type="button" class="agenda-calendar__navigation-button" data-testid="agenda-previous-month"
                        aria-label="Mês anterior" @click="goToPreviousMonth">
                        ‹
                    </button>

                    <button type="button" class="agenda-calendar__today-button" data-testid="agenda-today"
                        @click="goToToday">
                        Hoje
                    </button>

                    <button type="button" class="agenda-calendar__navigation-button" data-testid="agenda-next-month"
                        aria-label="Próximo mês" @click="goToNextMonth">
                        ›
                    </button>
                </div>

                <h2 id="agenda-calendar-title" class="agenda-calendar__title">
                    {{ currentMonthLabel }}
                </h2>
            </header>

            <div class="agenda-calendar__container">
                <div class="agenda-calendar__weekdays" aria-hidden="true">
                    <div v-for="weekday in weekdays" :key="weekday" class="agenda-calendar__weekday">
                        {{ weekday }}
                    </div>
                </div>

                <div class="agenda-calendar__grid">
                    <button v-for="day in calendarDays" :key="day.date" type="button" class="agenda-calendar__day"
                        :class="{
                            'agenda-calendar__day--outside':
                                !day.isCurrentMonth,

                            'agenda-calendar__day--today':
                                day.isToday,

                            'agenda-calendar__day--selected':
                                day.date === selectedDate,
                        }" data-testid="agenda-day" :data-date="day.date" :aria-label="day.date"
                        @click="selectDay(day)">
                        <span class="agenda-calendar__day-number">
                            {{ day.day }}
                        </span>

                        <span v-if="itemsForDate(day.date).length > 0" class="agenda-calendar__items">
                            <span v-for="item in itemsForDate(day.date)" :key="`${item.type}-${item.id}`"
                                class="agenda-calendar__item" :class="`agenda-calendar__item--${item.type}`"
                                :data-testid="`agenda-calendar-item-${item.type}-${item.id}`">
                                <span class="agenda-calendar__item-type">
                                    {{ itemTypeLabel(item.type) }}
                                </span>

                                <span class="agenda-calendar__item-title">
                                    {{ item.title }}
                                </span>
                            </span>
                        </span>
                    </button>
                </div>
            </div>
        </section>

        <!--
        |--------------------------------------------------------------------------
        | Dia selecionado
        |--------------------------------------------------------------------------
        -->

        <section class="agenda-selected-day" data-testid="agenda-selected-day"
            aria-labelledby="agenda-selected-day-title">
            <header class="agenda-selected-day__header">
                <div>
                    <h2 id="agenda-selected-day-title" class="agenda-selected-day__title">
                        {{ selectedDateLabel }}
                    </h2>

                    <p class="agenda-selected-day__description">
                        Tarefas, prazos e compromissos deste dia.
                    </p>
                </div>

                <span class="agenda-selected-day__count">
                    {{ selectedDayItems.length }}
                    {{ selectedDayItems.length === 1 ? 'item' : 'itens' }}
                </span>
            </header>

            <div v-if="selectedDayItems.length === 0" class="agenda-selected-day__empty">
                Nenhum item agendado para este dia.
            </div>

            <div v-else class="agenda-selected-day__items">
                <article v-for="item in selectedDayItems" :key="`${item.type}-${item.id}`" class="agenda-selected-item"
                    :class="`agenda-selected-item--${item.type}`"
                    :data-testid="`agenda-selected-item-${item.type}-${item.id}`">
                    <div class="agenda-selected-item__marker">
                        {{ itemTypeLabel(item.type) }}
                    </div>

                    <div class="agenda-selected-item__content">
                        <header class="agenda-selected-item__header">
                            <div>
                                <strong class="agenda-selected-item__title">
                                    {{ item.title }}
                                </strong>

                                <div class="agenda-selected-item__time">
                                    {{ formatItemTime(item) }}
                                </div>
                            </div>

                            <span class="agenda-selected-item__badge"
                                :class="`agenda-selected-item__badge--${item.type}`">
                                {{ itemTypeLabel(item.type) }}
                            </span>
                        </header>

                        <div v-if="item.type === 'task' && item.priority" class="agenda-selected-item__meta">
                            Prioridade:
                            {{ priorityLabel(item.priority) }}
                        </div>

                        <div v-if="item.type === 'event' && item.location" class="agenda-selected-item__meta">
                            Local:
                            {{ item.location }}
                        </div>

                        <div v-if="item.folder" class="agenda-selected-item__folder">
                            <div class="agenda-selected-item__folder-data">
                                <span class="agenda-selected-item__folder-name">
                                    {{ item.folder.name }}
                                </span>

                                <span v-if="item.folder.process_number" class="agenda-selected-item__process">
                                    {{ item.folder.process_number }}
                                </span>
                            </div>

                            <button type="button" class="agenda-selected-item__folder-button"
                                :data-testid="`agenda-selected-folder-${item.type}-${item.id}`"
                                @click="openFolder(item)">
                                Ver pasta
                            </button>
                        </div>

                        <div v-if="canUpdateFolders && canCompleteItem(item)" class="agenda-selected-item__actions">
                            <button v-if="item.type === 'task'" type="button"
                                class="agenda-selected-item__complete-button"
                                :data-testid="`agenda-complete-task-${item.id}`"
                                :disabled="completingItemKey === itemKey(item)" @click="completeTask(item)">
                                Concluir
                            </button>

                            <button v-else-if="item.type === 'deadline'" type="button"
                                class="agenda-selected-item__complete-button"
                                :data-testid="`agenda-complete-deadline-${item.id}`"
                                :disabled="completingItemKey === itemKey(item)" @click="completeDeadline(item)">
                                Concluir
                            </button>

                            <button v-else-if="item.type === 'event'" type="button"
                                class="agenda-selected-item__complete-button"
                                :data-testid="`agenda-complete-event-${item.id}`"
                                :disabled="completingItemKey === itemKey(item)" @click="completeEvent(item)">
                                Concluir
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </section>
</template>

<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'

import { useRouter } from 'vue-router'

import { useAgendaStore } from '@/stores/agenda.js'
import { useAuthStore } from '@/stores/auth.js'
import { useFolderDeadlinesStore } from '@/stores/folder-deadlines.js'
import { useFolderEventsStore } from '@/stores/folder-events.js'
import { useFolderTasksStore } from '@/stores/folder-tasks.js'

const router =
    useRouter()

const agendaStore =
    useAgendaStore()

const authStore =
    useAuthStore()

const folderTasksStore =
    useFolderTasksStore()

const folderDeadlinesStore =
    useFolderDeadlinesStore()

const folderEventsStore =
    useFolderEventsStore()

const weekdays = [
    'Seg',
    'Ter',
    'Qua',
    'Qui',
    'Sex',
    'Sáb',
    'Dom',
]

const monthNames = [
    'Janeiro',
    'Fevereiro',
    'Março',
    'Abril',
    'Maio',
    'Junho',
    'Julho',
    'Agosto',
    'Setembro',
    'Outubro',
    'Novembro',
    'Dezembro',
]

/*
|--------------------------------------------------------------------------
| Data atual
|--------------------------------------------------------------------------
*/

const today =
    new Date()

const todayReference = {
    year:
        today.getFullYear(),

    month:
        today.getMonth(),

    day:
        today.getDate(),
}

/*
|--------------------------------------------------------------------------
| Estado
|--------------------------------------------------------------------------
*/

const currentMonth =
    ref(
        new Date(
            todayReference.year,
            todayReference.month,
            1,
        ),
    )

const selectedDate =
    ref(
        formatDate(
            new Date(
                todayReference.year,
                todayReference.month,
                todayReference.day,
            ),
        ),
    )

const errorMessage =
    ref('')

const completingItemKey =
    ref(null)

/*
|--------------------------------------------------------------------------
| Permissões
|--------------------------------------------------------------------------
*/

const canUpdateFolders =
    computed(() =>
        authStore.hasPermission(
            'folders.update',
        ),
    )

/*
|--------------------------------------------------------------------------
| Mês atual
|--------------------------------------------------------------------------
*/

const currentMonthLabel =
    computed(() => {
        const month =
            monthNames[
            currentMonth.value.getMonth()
            ]

        const year =
            currentMonth.value.getFullYear()

        return `${month} ${year}`
    })

/*
|--------------------------------------------------------------------------
| Grade do calendário
|--------------------------------------------------------------------------
*/

const calendarDays =
    computed(() => {
        const year =
            currentMonth.value.getFullYear()

        const month =
            currentMonth.value.getMonth()

        const firstDayOfMonth =
            new Date(
                year,
                month,
                1,
            )

        const mondayOffset =
            (
                firstDayOfMonth.getDay()
                + 6
            ) % 7

        const calendarStart =
            new Date(
                year,
                month,
                1 - mondayOffset,
            )

        return Array.from(
            {
                length: 42,
            },

            (
                _,
                index,
            ) => {
                const date =
                    new Date(
                        calendarStart.getFullYear(),
                        calendarStart.getMonth(),
                        calendarStart.getDate() + index,
                    )

                return {
                    date:
                        formatDate(
                            date,
                        ),

                    day:
                        date.getDate(),

                    isCurrentMonth:
                        date.getMonth() === month
                        && date.getFullYear() === year,

                    isToday:
                        isSameDate(
                            date,

                            new Date(
                                todayReference.year,
                                todayReference.month,
                                todayReference.day,
                            ),
                        ),
                }
            },
        )
    })

/*
|--------------------------------------------------------------------------
| Itens agrupados por data
|--------------------------------------------------------------------------
*/

const itemsByDate =
    computed(() => {
        const grouped = {}

        for (
            const item of
            agendaStore.items
        ) {
            const date =
                itemDate(
                    item,
                )

            if (!date) {
                continue
            }

            if (!grouped[date]) {
                grouped[date] = []
            }

            grouped[date].push(
                item,
            )
        }

        for (
            const date of
            Object.keys(grouped)
        ) {
            grouped[date].sort(
                compareAgendaItems,
            )
        }

        return grouped
    })

/*
|--------------------------------------------------------------------------
| Dia selecionado
|--------------------------------------------------------------------------
*/

const selectedDayItems =
    computed(() =>
        itemsForDate(
            selectedDate.value,
        ),
    )

const selectedDateLabel =
    computed(() =>
        formatDateLabel(
            selectedDate.value,
        ),
    )

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(
    async () => {
        await loadCurrentMonth()
    },
)

/*
|--------------------------------------------------------------------------
| Agenda
|--------------------------------------------------------------------------
*/

async function loadCurrentMonth() {
    errorMessage.value = ''

    const year =
        currentMonth.value.getFullYear()

    const month =
        currentMonth.value.getMonth()

    const start =
        formatDate(
            new Date(
                year,
                month,
                1,
            ),
        )

    const end =
        formatDate(
            new Date(
                year,
                month + 1,
                0,
            ),
        )

    try {
        await agendaStore.fetchAgenda({
            start,
            end,
        })
    } catch {
        errorMessage.value =
            'Não foi possível carregar a agenda. Tente novamente.'
    }
}

/*
|--------------------------------------------------------------------------
| Navegação mensal
|--------------------------------------------------------------------------
*/

async function goToPreviousMonth() {
    currentMonth.value =
        new Date(
            currentMonth.value.getFullYear(),
            currentMonth.value.getMonth() - 1,
            1,
        )

    selectFirstDayOfCurrentMonth()

    await loadCurrentMonth()
}

async function goToNextMonth() {
    currentMonth.value =
        new Date(
            currentMonth.value.getFullYear(),
            currentMonth.value.getMonth() + 1,
            1,
        )

    selectFirstDayOfCurrentMonth()

    await loadCurrentMonth()
}

async function goToToday() {
    currentMonth.value =
        new Date(
            todayReference.year,
            todayReference.month,
            1,
        )

    selectedDate.value =
        formatDate(
            new Date(
                todayReference.year,
                todayReference.month,
                todayReference.day,
            ),
        )

    await loadCurrentMonth()
}

function selectFirstDayOfCurrentMonth() {
    selectedDate.value =
        formatDate(
            new Date(
                currentMonth.value.getFullYear(),
                currentMonth.value.getMonth(),
                1,
            ),
        )
}

/*
|--------------------------------------------------------------------------
| Seleção do dia
|--------------------------------------------------------------------------
*/

function selectDay(
    day,
) {
    selectedDate.value =
        day.date
}

/*
|--------------------------------------------------------------------------
| Pasta
|--------------------------------------------------------------------------
*/

async function openFolder(
    item,
) {
    const folderId =
        item?.folder?.id

    if (!folderId) {
        return
    }

    await router.push({
        name:
            'folders.show',

        params: {
            id:
                folderId,
        },
    })
}

/*
|--------------------------------------------------------------------------
| Conclusão
|--------------------------------------------------------------------------
*/

function canCompleteItem(
    item,
) {
    if (
        item.type === 'event'
    ) {
        return (
            item.status ===
            'scheduled'
        )
    }

    return (
        item.status ===
        'pending'
    )
}

async function completeTask(
    item,
) {
    await completeAgendaItem({
        item,

        action:
            () =>
                folderTasksStore.completeTask(
                    item.folder.id,
                    item.id,
                ),

        error:
            'Não foi possível concluir a tarefa. Tente novamente.',
    })
}

async function completeDeadline(
    item,
) {
    await completeAgendaItem({
        item,

        action:
            () =>
                folderDeadlinesStore.completeDeadline(
                    item.folder.id,
                    item.id,
                ),

        error:
            'Não foi possível concluir o prazo. Tente novamente.',
    })
}

async function completeEvent(
    item,
) {
    await completeAgendaItem({
        item,

        action:
            () =>
                folderEventsStore.completeEvent(
                    item.folder.id,
                    item.id,
                ),

        error:
            'Não foi possível concluir o compromisso. Tente novamente.',
    })
}

async function completeAgendaItem({
    item,
    action,
    error,
}) {
    if (
        !canUpdateFolders.value
        || !canCompleteItem(item)
        || !item?.folder?.id
    ) {
        return
    }

    errorMessage.value = ''

    completingItemKey.value =
        itemKey(
            item,
        )

    try {
        await action()

        await loadCurrentMonth()
    } catch {
        errorMessage.value =
            error
    } finally {
        completingItemKey.value =
            null
    }
}

function itemKey(
    item,
) {
    return `${item.type}-${item.id}`
}

/*
|--------------------------------------------------------------------------
| Helpers da Agenda
|--------------------------------------------------------------------------
*/

function itemsForDate(
    date,
) {
    return (
        itemsByDate.value[
        date
        ] ?? []
    )
}

function itemDate(
    item,
) {
    if (
        typeof item?.starts_at !==
        'string'
    ) {
        return null
    }

    const date =
        item.starts_at.slice(
            0,
            10,
        )

    return /^\d{4}-\d{2}-\d{2}$/.test(
        date,
    )
        ? date
        : null
}

function compareAgendaItems(
    first,
    second,
) {
    const firstDate =
        Date.parse(
            first?.starts_at ?? '',
        )

    const secondDate =
        Date.parse(
            second?.starts_at ?? '',
        )

    if (
        Number.isNaN(firstDate)
        && Number.isNaN(secondDate)
    ) {
        return 0
    }

    if (
        Number.isNaN(firstDate)
    ) {
        return 1
    }

    if (
        Number.isNaN(secondDate)
    ) {
        return -1
    }

    return (
        firstDate
        - secondDate
    )
}

function itemTypeLabel(
    type,
) {
    const labels = {
        task:
            'Tarefa',

        deadline:
            'Prazo',

        event:
            'Compromisso',
    }

    return (
        labels[type]
        ?? 'Item'
    )
}

function priorityLabel(
    priority,
) {
    const labels = {
        low:
            'Baixa',

        medium:
            'Média',

        high:
            'Alta',
    }

    return (
        labels[priority]
        ?? priority
    )
}

/*
|--------------------------------------------------------------------------
| Datas
|--------------------------------------------------------------------------
*/

function formatDate(
    date,
) {
    const year =
        date.getFullYear()

    const month =
        String(
            date.getMonth() + 1,
        )
            .padStart(
                2,
                '0',
            )

    const day =
        String(
            date.getDate(),
        )
            .padStart(
                2,
                '0',
            )

    return `${year}-${month}-${day}`
}

function parseDateKey(
    date,
) {
    const [
        year,
        month,
        day,
    ] =
        String(date)
            .split('-')
            .map(Number)

    if (
        !year
        || !month
        || !day
    ) {
        return null
    }

    return new Date(
        year,
        month - 1,
        day,
    )
}

function formatDateLabel(
    date,
) {
    const parsedDate =
        parseDateKey(
            date,
        )

    if (!parsedDate) {
        return ''
    }

    return new Intl.DateTimeFormat(
        'pt-BR',
        {
            day:
                'numeric',

            month:
                'long',

            year:
                'numeric',
        },
    ).format(
        parsedDate,
    )
}

function formatItemTime(
    item,
) {
    if (
        typeof item?.starts_at !==
        'string'
    ) {
        return ''
    }

    const parsed =
        new Date(
            item.starts_at,
        )

    if (
        Number.isNaN(
            parsed.getTime(),
        )
    ) {
        return ''
    }

    return new Intl.DateTimeFormat(
        'pt-BR',
        {
            hour:
                '2-digit',

            minute:
                '2-digit',
        },
    ).format(
        parsed,
    )
}

function isSameDate(
    first,
    second,
) {
    return (
        first.getFullYear() ===
        second.getFullYear()
        && first.getMonth() ===
        second.getMonth()
        && first.getDate() ===
        second.getDate()
    )
}
</script>

<style scoped>
.agenda-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.agenda-page__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.agenda-page__title {
    margin: 0;
}

.agenda-page__description {
    margin: var(--space-2) 0 0;
    color: var(--text-muted);
}

.agenda-page__alert {
    padding: var(--space-3) var(--space-4);
    border: 1px solid var(--danger);
    border-radius: var(--radius-md);
    color: var(--danger);
    background: var(--surface);
}

/*
|--------------------------------------------------------------------------
| Calendário
|--------------------------------------------------------------------------
*/

.agenda-calendar {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.agenda-calendar__toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
}

.agenda-calendar__navigation {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.agenda-calendar__navigation-button,
.agenda-calendar__today-button {
    min-height: 2.5rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--surface);
    color: var(--text-primary);
    cursor: pointer;
}

.agenda-calendar__navigation-button {
    width: 2.5rem;
    padding: 0;
    font-size: 1.4rem;
}

.agenda-calendar__today-button {
    padding: 0 var(--space-3);
    font: inherit;
}

.agenda-calendar__navigation-button:hover,
.agenda-calendar__today-button:hover {
    background: var(--surface-muted);
}

.agenda-calendar__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
}

.agenda-calendar__container {
    overflow: hidden;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    background: var(--surface);
}

.agenda-calendar__weekdays {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    border-bottom: 1px solid var(--border-color);
}

.agenda-calendar__weekday {
    padding: var(--space-3);
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
}

.agenda-calendar__grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
}

.agenda-calendar__day {
    position: relative;
    min-width: 0;
    min-height: 8.5rem;
    padding: var(--space-3);
    overflow: hidden;
    border: 0;
    border-right: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
    background: var(--surface);
    color: var(--text-primary);
    font: inherit;
    text-align: left;
    cursor: pointer;
}

.agenda-calendar__day:nth-child(7n) {
    border-right: 0;
}

.agenda-calendar__day:nth-last-child(-n + 7) {
    border-bottom: 0;
}

.agenda-calendar__day:hover {
    background: var(--surface-muted);
}

.agenda-calendar__day--outside {
    color: var(--text-muted);
    background: var(--surface-muted);
}

.agenda-calendar__day--today {
    box-shadow:
        inset 0 0 0 2px var(--primary);
}

.agenda-calendar__day--selected {
    background: var(--surface-muted);
}

.agenda-calendar__day-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.75rem;
    min-height: 1.75rem;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
}

.agenda-calendar__day--today .agenda-calendar__day-number {
    color: var(--primary);
    font-weight: 700;
}

/*
|--------------------------------------------------------------------------
| Itens no calendário
|--------------------------------------------------------------------------
*/

.agenda-calendar__items {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-top: var(--space-2);
}

.agenda-calendar__item {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
    padding: 0.3rem 0.4rem;
    overflow: hidden;
    border-radius: var(--radius-sm);
    background: var(--surface-muted);
    font-size: 0.72rem;
    line-height: 1.2;
}

.agenda-calendar__item--task {
    border-left: 3px solid var(--primary);
}

.agenda-calendar__item--deadline {
    border-left: 3px solid var(--warning, currentColor);
}

.agenda-calendar__item--event {
    border-left: 3px solid var(--success, currentColor);
}

.agenda-calendar__item-type {
    flex: 0 0 auto;
    font-weight: 700;
}

.agenda-calendar__item-title {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/*
|--------------------------------------------------------------------------
| Dia selecionado
|--------------------------------------------------------------------------
*/

.agenda-selected-day {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    padding: var(--space-5);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    background: var(--surface);
}

.agenda-selected-day__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.agenda-selected-day__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
}

.agenda-selected-day__description {
    margin: var(--space-1) 0 0;
    color: var(--text-muted);
}

.agenda-selected-day__count {
    flex: 0 0 auto;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: var(--surface-muted);
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 700;
}

.agenda-selected-day__empty {
    padding: var(--space-5);
    color: var(--text-muted);
    text-align: center;
}

.agenda-selected-day__items {
    display: flex;
    flex-direction: column;
}

.agenda-selected-item {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: var(--space-3);
    padding: var(--space-4) 0;
    border-bottom: 1px solid var(--border-color);
}

.agenda-selected-item:first-child {
    padding-top: 0;
}

.agenda-selected-item:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.agenda-selected-item__marker {
    align-self: flex-start;
    min-width: 6.5rem;
    padding: 0.35rem 0.6rem;
    border-radius: var(--radius-sm);
    background: var(--surface-muted);
    font-size: 0.75rem;
    font-weight: 700;
    text-align: center;
}

.agenda-selected-item__content {
    min-width: 0;
}

.agenda-selected-item__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-3);
}

.agenda-selected-item__title {
    display: block;
}

.agenda-selected-item__time {
    margin-top: 0.25rem;
    color: var(--text-muted);
    font-size: 0.85rem;
}

.agenda-selected-item__badge {
    flex: 0 0 auto;
    padding: 0.25rem 0.5rem;
    border-radius: 999px;
    background: var(--surface-muted);
    font-size: 0.7rem;
    font-weight: 700;
}

.agenda-selected-item__meta {
    margin-top: var(--space-2);
    color: var(--text-muted);
    font-size: 0.875rem;
}

/*
|--------------------------------------------------------------------------
| Pasta
|--------------------------------------------------------------------------
*/

.agenda-selected-item__folder {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    margin-top: var(--space-3);
}

.agenda-selected-item__folder-data {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
    min-width: 0;
}

.agenda-selected-item__folder-name {
    font-size: 0.875rem;
    font-weight: 600;
}

.agenda-selected-item__process {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.agenda-selected-item__folder-button {
    flex: 0 0 auto;
    min-height: 2rem;
    padding: 0.3rem 0.65rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--surface);
    color: var(--text-primary);
    font: inherit;
    font-size: 0.8rem;
    cursor: pointer;
}

.agenda-selected-item__folder-button:hover {
    background: var(--surface-muted);
}

/*
|--------------------------------------------------------------------------
| Ações
|--------------------------------------------------------------------------
*/

.agenda-selected-item__actions {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--space-3);
}

.agenda-selected-item__complete-button {
    min-height: 2rem;
    padding: 0.3rem 0.7rem;
    border: 1px solid var(--primary);
    border-radius: var(--radius-md);
    background: var(--primary);
    color: var(--on-primary, #fff);
    font: inherit;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
}

.agenda-selected-item__complete-button:disabled {
    opacity: 0.6;
    cursor: wait;
}

/*
|--------------------------------------------------------------------------
| Responsividade
|--------------------------------------------------------------------------
*/

@media (max-width: 900px) {
    .agenda-calendar__toolbar {
        align-items: flex-start;
        flex-direction: column-reverse;
    }

    .agenda-calendar__day {
        min-height: 7rem;
        padding: var(--space-2);
    }

    .agenda-selected-item {
        grid-template-columns: 1fr;
    }

    .agenda-selected-item__marker {
        width: fit-content;
    }
}

@media (max-width: 640px) {
    .agenda-calendar__container {
        overflow-x: auto;
    }

    .agenda-calendar__weekdays,
    .agenda-calendar__grid {
        min-width: 52rem;
    }

    .agenda-selected-day__header,
    .agenda-selected-item__header,
    .agenda-selected-item__folder {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>