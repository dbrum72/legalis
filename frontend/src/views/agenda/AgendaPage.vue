<template>
    <section class="agenda-page">
        <!--
        |--------------------------------------------------------------------------
        | Cabeçalho
        |--------------------------------------------------------------------------
        -->

        <header class="agenda-page__header">
            <div>
                <h1 class="agenda-page__title">
                    Agenda
                </h1>

                <p class="agenda-page__description">
                    Acompanhe tarefas, prazos e compromissos em uma visão de calendário.
                </p>
            </div>

            <div class="agenda-page__view-switcher" aria-label="Visualização da agenda">
                <button type="button" class="agenda-page__view-button" :class="{
                    'agenda-page__view-button--active':
                        currentView === 'month',
                }" data-testid="agenda-view-month" @click="setView('month')">
                    Mês
                </button>

                <button type="button" class="agenda-page__view-button" :class="{
                    'agenda-page__view-button--active':
                        currentView === 'list',
                }" data-testid="agenda-view-list" @click="setView('list')">
                    Lista
                </button>
            </div>
        </header>

        <!--
        |--------------------------------------------------------------------------
        | Mensagens
        |--------------------------------------------------------------------------
        -->

        <div v-if="errorMessage" class="agenda-page__alert" role="alert">
            {{ errorMessage }}
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Controles temporais
        |--------------------------------------------------------------------------
        -->

        <div class="agenda-period-toolbar">
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

            <div class="agenda-calendar__period">
                <span class="agenda-calendar__period-icon" aria-hidden="true">
                    ◫
                </span>

                <h2 id="agenda-calendar-title" class="agenda-calendar__title">
                    {{ currentMonthLabel }}
                </h2>
            </div>
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Visualização mensal
        |--------------------------------------------------------------------------
        -->

        <div v-if="currentView === 'month'" class="agenda-workspace" data-testid="agenda-calendar">
            <section class="agenda-calendar" aria-labelledby="agenda-calendar-title">
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
                            <span class="agenda-calendar__day-header">
                                <span class="agenda-calendar__day-number">
                                    {{ day.day }}
                                </span>

                                <span v-if="itemsForDate(day.date).length > 0" class="agenda-calendar__day-count">
                                    {{ itemsForDate(day.date).length }}
                                </span>
                            </span>

                            <span v-if="itemsForDate(day.date).length > 0" class="agenda-calendar__items">
                                <span v-for="item in visibleItemsForDate(day.date)" :key="`${item.type}-${item.id}`"
                                    class="agenda-calendar__item" :class="`agenda-calendar__item--${item.type}`"
                                    :data-testid="`agenda-calendar-item-${item.type}-${item.id}`">
                                    <span class="agenda-calendar__item-dot" aria-hidden="true"></span>

                                    <span class="agenda-calendar__item-type">
                                        {{ itemTypeLabel(item.type) }}
                                    </span>

                                    <span v-if="item.type === 'event'" class="agenda-calendar__item-time">
                                        {{ formatItemTime(item) }}
                                    </span>

                                    <span class="agenda-calendar__item-title">
                                        {{ item.title }}
                                    </span>
                                </span>

                                <span v-if="remainingItemsForDate(day.date) > 0" class="agenda-calendar__more">
                                    +{{ remainingItemsForDate(day.date) }}
                                    {{ remainingItemsForDate(day.date) === 1 ? 'item' : 'itens' }}
                                </span>
                            </span>
                        </button>
                    </div>
                </div>

                <footer class="agenda-calendar__legend">
                    <span class="agenda-calendar__legend-item">
                        <span class="agenda-calendar__legend-dot agenda-calendar__legend-dot--event"></span>

                        Compromissos
                    </span>

                    <span class="agenda-calendar__legend-item">
                        <span class="agenda-calendar__legend-dot agenda-calendar__legend-dot--task"></span>

                        Tarefas
                    </span>

                    <span class="agenda-calendar__legend-item">
                        <span class="agenda-calendar__legend-dot agenda-calendar__legend-dot--deadline"></span>

                        Prazos
                    </span>
                </footer>
            </section>

            <!--
            |--------------------------------------------------------------------------
            | Dia selecionado
            |--------------------------------------------------------------------------
            -->

            <aside class="agenda-selected-day" data-testid="agenda-selected-day"
                aria-labelledby="agenda-selected-day-title">
                <header class="agenda-selected-day__header">
                    <span class="agenda-selected-day__weekday">
                        {{ selectedWeekdayLabel }}
                    </span>

                    <h2 id="agenda-selected-day-title" class="agenda-selected-day__title">
                        {{ selectedDateLabel }}
                    </h2>

                    <div class="agenda-selected-day__summary">
                        <span class="agenda-selected-day__summary-icon" aria-hidden="true">
                            ◫
                        </span>

                        <span>
                            {{ selectedDayItems.length }}
                            {{ selectedDayItems.length === 1 ? 'item' : 'itens' }}
                        </span>
                    </div>
                </header>

                <div v-if="selectedDayItems.length === 0" class="agenda-selected-day__empty">
                    <span class="agenda-selected-day__empty-icon" aria-hidden="true">
                        ◫
                    </span>

                    <strong>
                        Nenhum compromisso neste dia
                    </strong>

                    <span>
                        Nenhum item agendado para este dia.
                    </span>
                </div>

                <div v-else class="agenda-selected-day__items">
                    <article v-for="item in selectedDayItems" :key="`${item.type}-${item.id}`"
                        class="agenda-selected-item" :class="`agenda-selected-item--${item.type}`"
                        :data-testid="`agenda-selected-item-${item.type}-${item.id}`">
                        <div class="agenda-selected-item__type">
                            <span class="agenda-selected-item__type-dot"></span>

                            {{ itemTypeLabel(item.type) }}
                        </div>

                        <div class="agenda-selected-item__card">
                            <header class="agenda-selected-item__header">
                                <span v-if="formatItemTime(item)" class="agenda-selected-item__time">
                                    {{ formatItemTime(item) }}
                                </span>

                                <strong class="agenda-selected-item__title">
                                    {{ item.title }}
                                </strong>
                            </header>

                            <dl class="agenda-selected-item__details">
                                <div v-if="item.type === 'task' && item.priority" class="agenda-selected-item__detail">
                                    <dt>
                                        Prioridade
                                    </dt>

                                    <dd>
                                        <span class="agenda-selected-item__priority"
                                            :class="`agenda-selected-item__priority--${item.priority}`">
                                            {{ priorityLabel(item.priority) }}
                                        </span>
                                    </dd>
                                </div>

                                <div v-if="item.type === 'event' && item.location" class="agenda-selected-item__detail">
                                    <dt>
                                        Local
                                    </dt>

                                    <dd>
                                        {{ item.location }}
                                    </dd>
                                </div>

                                <div v-if="item.folder" class="agenda-selected-item__detail">
                                    <dt>
                                        Pasta
                                    </dt>

                                    <dd>
                                        <strong>
                                            {{ item.folder.name }}
                                        </strong>

                                        <span v-if="item.folder.process_number" class="agenda-selected-item__process">
                                            {{ item.folder.process_number }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>

                            <footer class="agenda-selected-item__actions-row">
                                <button v-if="item.folder" type="button" class="agenda-selected-item__folder-button"
                                    :data-testid="`agenda-selected-folder-${item.type}-${item.id}`"
                                    @click="openFolder(item)">
                                    Ver pasta

                                    <span aria-hidden="true">
                                        →
                                    </span>
                                </button>

                                <button v-if="
                                    canUpdateFolders
                                    && canCompleteItem(item)
                                    && item.type === 'task'
                                " type="button" class="agenda-selected-item__complete-button"
                                    :data-testid="`agenda-complete-task-${item.id}`"
                                    :disabled="completingItemKey === itemKey(item)" @click="completeTask(item)">
                                    ✓
                                    Concluir tarefa
                                </button>

                                <button v-else-if="
                                    canUpdateFolders
                                    && canCompleteItem(item)
                                    && item.type === 'deadline'
                                " type="button" class="agenda-selected-item__complete-button"
                                    :data-testid="`agenda-complete-deadline-${item.id}`"
                                    :disabled="completingItemKey === itemKey(item)" @click="completeDeadline(item)">
                                    ✓
                                    Concluir prazo
                                </button>

                                <button v-else-if="
                                    canUpdateFolders
                                    && canCompleteItem(item)
                                    && item.type === 'event'
                                " type="button" class="agenda-selected-item__complete-button"
                                    :data-testid="`agenda-complete-event-${item.id}`"
                                    :disabled="completingItemKey === itemKey(item)" @click="completeEvent(item)">
                                    ✓
                                    Concluir compromisso
                                </button>
                            </footer>
                        </div>
                    </article>
                </div>

                <footer v-if="selectedDayItems.length > 0" class="agenda-selected-day__totals">
                    <h3 class="agenda-selected-day__totals-title">
                        Resumo do dia
                    </h3>

                    <div class="agenda-selected-day__totals-grid">
                        <div class="agenda-selected-day__total">
                            <span class="agenda-selected-day__total-value">
                                {{ selectedDayTypeCount('event') }}
                            </span>

                            <span class="agenda-selected-day__total-label">
                                Compromissos
                            </span>
                        </div>

                        <div class="agenda-selected-day__total">
                            <span class="agenda-selected-day__total-value">
                                {{ selectedDayTypeCount('task') }}
                            </span>

                            <span class="agenda-selected-day__total-label">
                                Tarefas
                            </span>
                        </div>

                        <div class="agenda-selected-day__total">
                            <span class="agenda-selected-day__total-value">
                                {{ selectedDayTypeCount('deadline') }}
                            </span>

                            <span class="agenda-selected-day__total-label">
                                Prazos
                            </span>
                        </div>
                    </div>
                </footer>
            </aside>
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Visualização em lista
        |--------------------------------------------------------------------------
        -->

        <section v-else class="agenda-list" data-testid="agenda-list" aria-label="Agenda em lista">
            <div v-if="agendaListGroups.length === 0" class="agenda-list__empty">
                <span class="agenda-list__empty-icon" aria-hidden="true">
                    ◫
                </span>

                <strong>
                    Nenhum item encontrado
                </strong>

                <span>
                    Nenhum item encontrado neste período.
                </span>
            </div>

            <div v-else class="agenda-list__groups">
                <section v-for="group in agendaListGroups" :key="group.date" class="agenda-list__group">
                    <header class="agenda-list__group-header">
                        <div>
                            <span class="agenda-list__weekday">
                                {{ formatWeekday(group.date) }}
                            </span>

                            <h3 class="agenda-list__date">
                                {{ formatDateLabel(group.date) }}
                            </h3>
                        </div>

                        <span class="agenda-list__count">
                            {{ group.items.length }}
                            {{ group.items.length === 1 ? 'item' : 'itens' }}
                        </span>
                    </header>

                    <div class="agenda-list__items">
                        <article v-for="item in group.items" :key="`${item.type}-${item.id}`" class="agenda-list-item"
                            :class="`agenda-list-item--${item.type}`"
                            :data-testid="`agenda-list-item-${item.type}-${item.id}`">
                            <div class="agenda-list-item__time">
                                {{ formatItemTime(item) }}
                            </div>

                            <div class="agenda-list-item__marker">
                                <span class="agenda-list-item__dot"></span>
                            </div>

                            <div class="agenda-list-item__content">
                                <div class="agenda-list-item__heading">
                                    <span class="agenda-list-item__type">
                                        {{ itemTypeLabel(item.type) }}
                                    </span>

                                    <strong class="agenda-list-item__title">
                                        {{ item.title }}
                                    </strong>
                                </div>

                                <div v-if="item.folder" class="agenda-list-item__folder">
                                    <span>
                                        {{ item.folder.name }}
                                    </span>

                                    <span v-if="item.folder.process_number" class="agenda-list-item__process">
                                        {{ item.folder.process_number }}
                                    </span>
                                </div>

                                <div v-if="item.type === 'event' && item.location" class="agenda-list-item__meta">
                                    {{ item.location }}
                                </div>

                                <div v-if="item.type === 'task' && item.priority" class="agenda-list-item__meta">
                                    Prioridade:
                                    {{ priorityLabel(item.priority) }}
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
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

const currentView =
    ref('month')

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
| Visualização
|--------------------------------------------------------------------------
*/

function setView(
    view,
) {
    if (
        view !== 'month'
        && view !== 'list'
    ) {
        return
    }

    currentView.value =
        view
}

/*
|--------------------------------------------------------------------------
| Mês
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

        return `${month} de ${year}`
    })

/*
|--------------------------------------------------------------------------
| Grade mensal
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
| Lista
|--------------------------------------------------------------------------
*/

const agendaListGroups =
    computed(() => {
        return Object
            .keys(
                itemsByDate.value,
            )
            .sort()
            .map(
                (date) => ({
                    date,

                    items:
                        itemsByDate.value[
                        date
                        ],
                }),
            )
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

const selectedWeekdayLabel =
    computed(() =>
        formatWeekday(
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
| Seleção
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
| Itens visíveis no calendário
|--------------------------------------------------------------------------
*/

function visibleItemsForDate(
    date,
) {
    return itemsForDate(
        date,
    ).slice(
        0,
        2,
    )
}

function remainingItemsForDate(
    date,
) {
    return Math.max(
        itemsForDate(
            date,
        ).length - 2,
        0,
    )
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
| Helpers
|--------------------------------------------------------------------------
*/

function selectedDayTypeCount(
    type,
) {
    return selectedDayItems.value.filter(
        (item) =>
            item.type === type,
    ).length
}

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

function formatWeekday(
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
            weekday:
                'long',
        },
    )
        .format(
            parsedDate,
        )
        .toUpperCase()
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
    --agenda-surface:
        var(--surface, #fffdf8);

    --agenda-surface-soft:
        var(--surface-muted, #f7f3e9);

    --agenda-border:
        var(--border-color, #e8e0d2);

    --agenda-text:
        var(--text-primary, #3f352a);

    --agenda-muted:
        var(--text-muted, #817566);

    --agenda-primary:
        var(--primary, #557c32);

    --agenda-primary-soft:
        #edf2e4;

    --agenda-primary-soft-strong:
        #e4ecd8;

    --agenda-event:
        #e78529;

    --agenda-event-soft:
        #fff0df;

    --agenda-task:
        #4f7e3a;

    --agenda-task-soft:
        #edf4e7;

    --agenda-deadline:
        #c9544d;

    --agenda-deadline-soft:
        #fbe9e5;

    display: flex;
    flex-direction: column;
    gap: var(--space-5, 1.25rem);

    color:
        var(--agenda-text);
}

/*
|--------------------------------------------------------------------------
| Cabeçalho
|--------------------------------------------------------------------------
*/

.agenda-page__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4, 1rem);
}

.agenda-page__title {
    margin: 0;

    color:
        var(--agenda-text);

    font-size:
        clamp(1.55rem, 2.2vw, 2rem);

    font-weight: 750;
    letter-spacing: -0.03em;
}

.agenda-page__description {
    margin:
        var(--space-1, 0.25rem) 0 0;

    color:
        var(--agenda-muted);

    font-size:
        0.92rem;
}

/*
|--------------------------------------------------------------------------
| Seletor de visualização
|--------------------------------------------------------------------------
*/

.agenda-page__view-switcher {
    display: inline-flex;
    align-items: center;

    padding: 0.2rem;

    border:
        1px solid var(--agenda-border);

    border-radius:
        var(--radius-md, 0.65rem);

    background:
        var(--agenda-surface-soft);
}

.agenda-page__view-button {
    min-width: 4.4rem;
    height: 2.2rem;

    padding:
        0 0.8rem;

    border: 0;

    border-radius:
        calc(var(--radius-md, 0.65rem) - 0.15rem);

    color:
        var(--agenda-muted);

    background:
        transparent;

    font: inherit;
    font-size: 0.78rem;
    font-weight: 700;

    cursor: pointer;
}

.agenda-page__view-button--active {
    color:
        var(--agenda-primary);

    background:
        var(--agenda-surface);

    box-shadow:
        0 1px 4px rgb(70 55 35 / 9%);
}

/*
|--------------------------------------------------------------------------
| Alerta
|--------------------------------------------------------------------------
*/

.agenda-page__alert {
    padding:
        var(--space-3, 0.75rem) var(--space-4, 1rem);

    border:
        1px solid var(--danger, #c9544d);

    border-radius:
        var(--radius-md, 0.625rem);

    color:
        var(--danger, #a9433d);

    background:
        #fff5f2;
}

/*
|--------------------------------------------------------------------------
| Navegação temporal
|--------------------------------------------------------------------------
*/

.agenda-period-toolbar {
    display: flex;
    align-items: center;
    gap: 1.2rem;
}

.agenda-calendar__navigation {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.agenda-calendar__navigation-button,
.agenda-calendar__today-button {
    height: 2.55rem;

    border:
        1px solid var(--agenda-border);

    background:
        var(--agenda-surface);

    color:
        var(--agenda-text);

    font: inherit;
    cursor: pointer;
}

.agenda-calendar__navigation-button {
    width: 2.55rem;
    padding: 0;

    border-radius:
        var(--radius-md, 0.625rem);

    font-size:
        1.2rem;
}

.agenda-calendar__today-button {
    padding:
        0 0.9rem;

    border-radius:
        var(--radius-md, 0.625rem);

    font-size:
        0.84rem;

    font-weight:
        650;
}

.agenda-calendar__navigation-button:hover,
.agenda-calendar__today-button:hover {
    background:
        var(--agenda-primary-soft);
}

.agenda-calendar__period {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.agenda-calendar__period-icon {
    color:
        var(--agenda-muted);
}

.agenda-calendar__title {
    margin: 0;

    font-size:
        1.15rem;

    font-weight:
        720;
}

/*
|--------------------------------------------------------------------------
| Workspace mensal
|--------------------------------------------------------------------------
*/

.agenda-workspace {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr) minmax(19rem, 23rem);

    align-items:
        start;

    gap:
        var(--space-5, 1.25rem);
}

.agenda-calendar {
    min-width: 0;
}

.agenda-calendar__container {
    overflow: hidden;

    border:
        1px solid var(--agenda-border);

    border-radius:
        var(--radius-lg, 0.9rem);

    background:
        var(--agenda-surface);
}

.agenda-calendar__weekdays {
    display: grid;

    grid-template-columns:
        repeat(7,
            minmax(0, 1fr));

    border-bottom:
        1px solid var(--agenda-border);
}

.agenda-calendar__weekday {
    padding:
        0.85rem 0.5rem;

    color:
        var(--agenda-muted);

    font-size:
        0.72rem;

    font-weight:
        750;

    text-align:
        center;

    text-transform:
        uppercase;

    letter-spacing:
        0.055em;
}

.agenda-calendar__grid {
    display: grid;

    grid-template-columns:
        repeat(7,
            minmax(0, 1fr));
}

.agenda-calendar__day {
    position: relative;

    display: flex;
    flex-direction: column;

    min-width: 0;
    min-height: 8.25rem;

    padding: 0.65rem;

    overflow: hidden;

    border: 0;

    border-right:
        1px solid var(--agenda-border);

    border-bottom:
        1px solid var(--agenda-border);

    background:
        var(--agenda-surface);

    color:
        var(--agenda-text);

    font: inherit;

    text-align:
        left;

    cursor:
        pointer;
}

.agenda-calendar__day:nth-child(7n) {
    border-right: 0;
}

.agenda-calendar__day:nth-last-child(-n + 7) {
    border-bottom: 0;
}

.agenda-calendar__day:hover {
    background:
        var(--agenda-primary-soft);
}

.agenda-calendar__day--outside {
    color:
        var(--agenda-muted);

    background:
        var(--agenda-surface-soft);
}

.agenda-calendar__day--selected {
    background:
        var(--agenda-primary-soft);
}

.agenda-calendar__day--today {
    box-shadow:
        inset 0 0 0 2px var(--agenda-primary);
}

.agenda-calendar__day-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    min-height:
        1.9rem;
}

.agenda-calendar__day-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width:
        1.85rem;

    height:
        1.85rem;

    border-radius:
        999px;

    font-size:
        0.84rem;

    font-weight:
        650;
}

.agenda-calendar__day--today .agenda-calendar__day-number {
    color: #fff;

    background:
        var(--agenda-primary);
}

.agenda-calendar__day-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width: 1.35rem;
    height: 1.35rem;

    padding:
        0 0.3rem;

    border-radius:
        999px;

    color: #fff;

    background:
        var(--agenda-primary);

    font-size:
        0.65rem;

    font-weight:
        750;
}

/*
|--------------------------------------------------------------------------
| Itens do calendário
|--------------------------------------------------------------------------
*/

.agenda-calendar__items {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;

    margin-top:
        0.45rem;
}

.agenda-calendar__item {
    display: flex;
    align-items: center;
    gap: 0.3rem;

    min-width: 0;

    padding:
        0.3rem 0.4rem;

    border-radius:
        0.38rem;

    font-size:
        0.67rem;
}

.agenda-calendar__item--event {
    background:
        var(--agenda-event-soft);
}

.agenda-calendar__item--task {
    background:
        var(--agenda-task-soft);
}

.agenda-calendar__item--deadline {
    background:
        var(--agenda-deadline-soft);
}

.agenda-calendar__item-dot {
    flex: 0 0 auto;

    width: 0.38rem;
    height: 0.38rem;

    border-radius:
        999px;
}

.agenda-calendar__item--event .agenda-calendar__item-dot {
    background:
        var(--agenda-event);
}

.agenda-calendar__item--task .agenda-calendar__item-dot {
    background:
        var(--agenda-task);
}

.agenda-calendar__item--deadline .agenda-calendar__item-dot {
    background:
        var(--agenda-deadline);
}

.agenda-calendar__item-type {
    position: absolute;

    width: 1px;
    height: 1px;

    padding: 0;

    overflow: hidden;

    clip:
        rect(0, 0, 0, 0);

    white-space:
        nowrap;

    border: 0;
}

.agenda-calendar__item-time {
    flex: 0 0 auto;
    font-weight: 750;
}

.agenda-calendar__item-title {
    min-width: 0;

    overflow: hidden;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;
}

.agenda-calendar__more {
    padding-left:
        0.72rem;

    color:
        var(--agenda-primary);

    font-size:
        0.67rem;

    font-weight:
        700;
}

.agenda-calendar__legend {
    display: flex;
    justify-content: center;
    gap: 1.2rem;

    margin-top:
        0.8rem;

    color:
        var(--agenda-muted);

    font-size:
        0.74rem;
}

.agenda-calendar__legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.38rem;
}

.agenda-calendar__legend-dot {
    width: 0.45rem;
    height: 0.45rem;

    border-radius:
        999px;
}

.agenda-calendar__legend-dot--event {
    background:
        var(--agenda-event);
}

.agenda-calendar__legend-dot--task {
    background:
        var(--agenda-task);
}

.agenda-calendar__legend-dot--deadline {
    background:
        var(--agenda-deadline);
}

/*
|--------------------------------------------------------------------------
| Painel do dia
|--------------------------------------------------------------------------
*/

.agenda-selected-day {
    position: sticky;
    top: 1rem;

    overflow: hidden;

    border:
        1px solid var(--agenda-border);

    border-radius:
        var(--radius-lg, 0.9rem);

    background:
        var(--agenda-surface);
}

.agenda-selected-day__header {
    padding: 1.25rem;

    border-bottom:
        1px solid var(--agenda-border);
}

.agenda-selected-day__weekday {
    display: block;

    margin-bottom:
        0.45rem;

    color:
        var(--agenda-primary);

    font-size:
        0.7rem;

    font-weight:
        800;
}

.agenda-selected-day__title {
    margin: 0;

    font-size:
        1.25rem;

    font-weight:
        740;
}

.agenda-selected-day__summary {
    display: flex;
    gap: 0.45rem;

    margin-top:
        0.85rem;

    color:
        var(--agenda-muted);

    font-size:
        0.8rem;
}

.agenda-selected-day__items {
    padding: 1.1rem;
}

.agenda-selected-day__empty {
    display: flex;
    flex-direction: column;
    align-items: center;

    gap: 0.5rem;

    min-height:
        18rem;

    padding: 2rem;

    color:
        var(--agenda-muted);

    text-align:
        center;
}

.agenda-selected-day__empty-icon {
    font-size:
        1.3rem;

    color:
        var(--agenda-primary);
}

.agenda-selected-item {
    padding-bottom:
        1rem;
}

.agenda-selected-item+.agenda-selected-item {
    margin-top:
        1rem;

    padding-top:
        1rem;

    border-top:
        1px solid var(--agenda-border);
}

.agenda-selected-item__type {
    display: flex;
    align-items: center;
    gap: 0.4rem;

    margin-bottom:
        0.55rem;

    font-size:
        0.69rem;

    font-weight:
        800;

    text-transform:
        uppercase;
}

.agenda-selected-item--event .agenda-selected-item__type {
    color:
        var(--agenda-event);
}

.agenda-selected-item--task .agenda-selected-item__type {
    color:
        var(--agenda-task);
}

.agenda-selected-item--deadline .agenda-selected-item__type {
    color:
        var(--agenda-deadline);
}

.agenda-selected-item__type-dot {
    width: 0.45rem;
    height: 0.45rem;

    border-radius:
        999px;

    background:
        currentColor;
}

.agenda-selected-item__card {
    padding: 1rem;

    border:
        1px solid var(--agenda-border);

    border-left-width:
        3px;

    border-radius:
        var(--radius-md, 0.65rem);

    background:
        #fff;
}

.agenda-selected-item--event .agenda-selected-item__card {
    border-left-color:
        var(--agenda-event);
}

.agenda-selected-item--task .agenda-selected-item__card {
    border-left-color:
        var(--agenda-task);
}

.agenda-selected-item--deadline .agenda-selected-item__card {
    border-left-color:
        var(--agenda-deadline);
}

.agenda-selected-item__header {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.agenda-selected-item__time {
    font-size:
        0.72rem;

    font-weight:
        750;
}

.agenda-selected-item__details {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;

    margin-top:
        0.9rem;
}

.agenda-selected-item__detail {
    display: grid;

    grid-template-columns:
        5.2rem minmax(0, 1fr);

    gap: 0.65rem;

    font-size:
        0.76rem;
}

.agenda-selected-item__detail dt {
    color:
        var(--agenda-muted);
}

.agenda-selected-item__detail dd {
    display: flex;
    flex-direction: column;

    margin: 0;
}

.agenda-selected-item__process {
    color:
        var(--agenda-muted);

    font-size:
        0.71rem;
}

.agenda-selected-item__priority {
    width: fit-content;

    padding:
        0.16rem 0.4rem;

    border-radius:
        999px;
}

.agenda-selected-item__priority--high {
    background:
        var(--agenda-deadline-soft);
}

.agenda-selected-item__priority--medium {
    background:
        #fff3dc;
}

.agenda-selected-item__priority--low {
    background:
        var(--agenda-task-soft);
}

.agenda-selected-item__actions-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;

    margin-top:
        1rem;
}

.agenda-selected-item__folder-button,
.agenda-selected-item__complete-button {
    min-height:
        2.15rem;

    padding:
        0.34rem 0.65rem;

    border-radius:
        var(--radius-md, 0.6rem);

    font:
        inherit;

    font-size:
        0.73rem;

    cursor:
        pointer;
}

.agenda-selected-item__folder-button {
    border:
        1px solid var(--agenda-border);

    background:
        var(--agenda-surface);
}

.agenda-selected-item__complete-button {
    border:
        1px solid var(--agenda-primary);

    color:
        var(--agenda-primary);

    background:
        var(--agenda-primary-soft);
}

.agenda-selected-day__totals {
    padding: 1rem;

    border-top:
        1px solid var(--agenda-border);
}

.agenda-selected-day__totals-title {
    margin:
        0 0 0.7rem;

    font-size:
        0.75rem;
}

.agenda-selected-day__totals-grid {
    display: grid;

    grid-template-columns:
        repeat(3,
            1fr);

    gap: 0.5rem;
}

.agenda-selected-day__total {
    display: flex;
    flex-direction: column;
    align-items: center;

    padding: 0.65rem;

    border:
        1px solid var(--agenda-border);

    border-radius:
        var(--radius-md, 0.6rem);
}

.agenda-selected-day__total-value {
    font-weight:
        800;
}

.agenda-selected-day__total-label {
    color:
        var(--agenda-muted);

    font-size:
        0.6rem;
}

/*
|--------------------------------------------------------------------------
| Lista
|--------------------------------------------------------------------------
*/

.agenda-list {
    max-width:
        70rem;

    border:
        1px solid var(--agenda-border);

    border-radius:
        var(--radius-lg, 0.9rem);

    background:
        var(--agenda-surface);

    overflow:
        hidden;
}

.agenda-list__groups {
    display: flex;
    flex-direction: column;
}

.agenda-list__group+.agenda-list__group {
    border-top:
        1px solid var(--agenda-border);
}

.agenda-list__group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap:
        1rem;

    padding:
        1rem 1.25rem;

    background:
        var(--agenda-surface-soft);
}

.agenda-list__weekday {
    display: block;

    margin-bottom:
        0.2rem;

    color:
        var(--agenda-primary);

    font-size:
        0.66rem;

    font-weight:
        800;

    letter-spacing:
        0.055em;
}

.agenda-list__date {
    margin: 0;

    font-size:
        1rem;

    font-weight:
        720;
}

.agenda-list__count {
    padding:
        0.25rem 0.55rem;

    border-radius:
        999px;

    color:
        var(--agenda-muted);

    background:
        var(--agenda-surface);

    font-size:
        0.7rem;

    font-weight:
        700;
}

.agenda-list__items {
    display: flex;
    flex-direction: column;
}

.agenda-list-item {
    display: grid;

    grid-template-columns:
        4.5rem 0.8rem minmax(0, 1fr);

    gap:
        0.8rem;

    align-items:
        start;

    padding:
        1rem 1.25rem;
}

.agenda-list-item+.agenda-list-item {
    border-top:
        1px solid var(--agenda-border);
}

.agenda-list-item__time {
    padding-top:
        0.1rem;

    color:
        var(--agenda-muted);

    font-size:
        0.78rem;

    font-weight:
        700;
}

.agenda-list-item__marker {
    display: flex;
    justify-content: center;

    padding-top:
        0.35rem;
}

.agenda-list-item__dot {
    width:
        0.5rem;

    height:
        0.5rem;

    border-radius:
        999px;
}

.agenda-list-item--event .agenda-list-item__dot {
    background:
        var(--agenda-event);
}

.agenda-list-item--task .agenda-list-item__dot {
    background:
        var(--agenda-task);
}

.agenda-list-item--deadline .agenda-list-item__dot {
    background:
        var(--agenda-deadline);
}

.agenda-list-item__content {
    min-width: 0;
}

.agenda-list-item__heading {
    display: flex;
    align-items: baseline;
    gap: 0.7rem;
}

.agenda-list-item__type {
    flex: 0 0 auto;

    font-size:
        0.68rem;

    font-weight:
        800;

    text-transform:
        uppercase;

    letter-spacing:
        0.045em;
}

.agenda-list-item--event .agenda-list-item__type {
    color:
        var(--agenda-event);
}

.agenda-list-item--task .agenda-list-item__type {
    color:
        var(--agenda-task);
}

.agenda-list-item--deadline .agenda-list-item__type {
    color:
        var(--agenda-deadline);
}

.agenda-list-item__title {
    font-size:
        0.9rem;
}

.agenda-list-item__folder {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;

    margin-top:
        0.4rem;

    color:
        var(--agenda-muted);

    font-size:
        0.76rem;
}

.agenda-list-item__process::before {
    content: '•';

    margin-right:
        0.5rem;
}

.agenda-list-item__meta {
    margin-top:
        0.25rem;

    color:
        var(--agenda-muted);

    font-size:
        0.74rem;
}

.agenda-list__empty {
    display: flex;
    flex-direction: column;
    align-items: center;

    gap:
        0.5rem;

    padding:
        4rem 2rem;

    color:
        var(--agenda-muted);

    text-align:
        center;
}

.agenda-list__empty-icon {
    color:
        var(--agenda-primary);

    font-size:
        1.5rem;
}

.agenda-list__empty strong {
    color:
        var(--agenda-text);
}

/*
|--------------------------------------------------------------------------
| Responsividade
|--------------------------------------------------------------------------
*/

@media (max-width: 960px) {
    .agenda-workspace {
        grid-template-columns:
            1fr;
    }

    .agenda-selected-day {
        position:
            static;
    }
}

@media (max-width: 760px) {
    .agenda-page__header {
        flex-direction:
            column;
    }

    .agenda-period-toolbar {
        align-items:
            flex-start;

        flex-direction:
            column-reverse;
    }

    .agenda-calendar__container {
        overflow-x:
            auto;
    }

    .agenda-calendar__weekdays,
    .agenda-calendar__grid {
        min-width:
            49rem;
    }
}

@media (max-width: 560px) {
    .agenda-page__view-switcher {
        width:
            100%;
    }

    .agenda-page__view-button {
        flex:
            1 1 0;
    }

    .agenda-list-item {
        grid-template-columns:
            auto minmax(0, 1fr);

        gap:
            0.65rem;
    }

    .agenda-list-item__time {
        grid-column:
            1 / -1;
    }

    .agenda-list-item__marker {
        grid-column:
            1;
    }

    .agenda-list-item__content {
        grid-column:
            2;
    }

    .agenda-list-item__heading {
        align-items:
            flex-start;

        flex-direction:
            column;

        gap:
            0.25rem;
    }
}
</style>