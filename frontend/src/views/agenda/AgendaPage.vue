<template>
    <PageContainer>
        <section class="agenda-page">
            <!--
        |--------------------------------------------------------------------------
        | Cabeçalho
        |--------------------------------------------------------------------------
        -->

            <header class="agenda-page__header">
                <div class="agenda-page__heading">
                    <h1 class="agenda-page__title">
                        Agenda
                    </h1>

                    <p class="agenda-page__description">
                        Acompanhe tarefas, prazos e compromissos em uma visão de calendário.
                    </p>
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
        | Toolbar
        |--------------------------------------------------------------------------
        -->

            <section class="agenda-toolbar" aria-label="Controles da agenda">
                <!--
            |--------------------------------------------------------------------------
            | Navegação temporal
            |--------------------------------------------------------------------------
            -->

                <div class="agenda-toolbar__period">
                    <div class="agenda-calendar__navigation">
                        <button type="button" class="agenda-calendar__navigation-button"
                            data-testid="agenda-previous-month" aria-label="Mês anterior" @click="goToPreviousMonth">
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
                        {{ currentPeriodLabel }}
                    </h2>
                </div>

                <!--
            |--------------------------------------------------------------------------
            | Filtros
            |--------------------------------------------------------------------------
            -->

                <div class="agenda-toolbar__filters">
                    <div class="agenda-select-field">
                        <label for="agenda-filter-type" class="agenda-select-field__label">
                            Tipo
                        </label>

                        <div class="agenda-select-field__control">
                            <select id="agenda-filter-type" v-model="currentFilter" class="agenda-select-field__select"
                                data-testid="agenda-filter-type">
                                <option value="all">
                                    Todos os tipos
                                </option>

                                <option value="deadline">
                                    Prazos
                                </option>

                                <option value="task">
                                    Tarefas
                                </option>

                                <option value="event">
                                    Compromissos
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="agenda-select-field">
                        <label for="agenda-filter-status" class="agenda-select-field__label">
                            Situação
                        </label>

                        <div class="agenda-select-field__control">
                            <select id="agenda-filter-status" v-model="currentStatusFilter"
                                class="agenda-select-field__select" data-testid="agenda-filter-status">
                                <option value="all">
                                    Todas as situações
                                </option>

                                <option value="pending">
                                    Pendentes
                                </option>

                                <option value="completed">
                                    Concluídos
                                </option>

                                <option value="overdue">
                                    Vencidos
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!--
            |--------------------------------------------------------------------------
            | Visualização
            |--------------------------------------------------------------------------
            -->

                <div class="agenda-page__view-switcher" aria-label="Visualização da agenda">
                    <button type="button" class="agenda-page__view-button" :class="{
                        'agenda-page__view-button--active':
                            currentView === 'month',
                    }" data-testid="agenda-view-month" @click="setView('month')">
                        Mês
                    </button>

                    <button type="button" class="agenda-page__view-button" :class="{
                        'agenda-page__view-button--active':
                            currentView === 'week',
                    }" data-testid="agenda-view-week" @click="setView('week')">
                        Semana
                    </button>

                    <button type="button" class="agenda-page__view-button" :class="{
                        'agenda-page__view-button--active':
                            currentView === 'list',
                    }" data-testid="agenda-view-list" @click="setView('list')">
                        Lista
                    </button>
                </div>
            </section>

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
                            <button v-for="day in calendarDays" :key="day.date" type="button"
                                class="agenda-calendar__day" :class="{
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
                                            {{ folderItemTypeLabel(
                                                item.type,
                                                {
                                                    fallback: 'Item',
                                                    preserveUnknown: false,
                                                },
                                            ) }}
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
                            <span>
                                {{ selectedDayItems.length }}
                                {{ selectedDayItems.length === 1 ? 'item' : 'itens' }}
                            </span>
                        </div>
                    </header>

                    <div v-if="selectedDayItems.length === 0" class="agenda-selected-day__empty">
                        <div class="agenda-selected-day__empty-mark" aria-hidden="true"></div>

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

                                {{ folderItemTypeLabel(
                                    item.type,
                                    {
                                        fallback: 'Item',
                                        preserveUnknown: false,
                                    },
                                ) }}
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
                                    <div v-if="item.type === 'task' && item.priority"
                                        class="agenda-selected-item__detail">
                                        <dt>
                                            Prioridade
                                        </dt>

                                        <dd>
                                            <span class="agenda-selected-item__priority"
                                                :class="`agenda-selected-item__priority--${item.priority}`">
                                                {{ folderPriorityLabel(item.priority) }}
                                            </span>
                                        </dd>
                                    </div>

                                    <div v-if="item.type === 'event' && item.location"
                                        class="agenda-selected-item__detail">
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

                                            <span v-if="item.folder.process_number"
                                                class="agenda-selected-item__process">
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
| Visualização semanal
|--------------------------------------------------------------------------
-->

            <section v-else-if="currentView === 'week'" class="agenda-week" data-testid="agenda-week"
                aria-labelledby="agenda-calendar-title">
                <div class="agenda-week__viewport">
                    <div class="agenda-week__grid">
                        <article v-for="day in weekDays" :key="day.date" class="agenda-week__day" :class="{
                            'agenda-week__day--today':
                                day.isToday,
                        }" data-testid="agenda-week-day" :data-date="day.date">
                            <header class="agenda-week__day-header">
                                <span class="agenda-week__weekday">
                                    {{ day.weekday }}
                                </span>

                                <span class="agenda-week__day-number">
                                    {{ day.day }}
                                </span>

                                <span v-if="itemsForDate(day.date).length > 0" class="agenda-week__count">
                                    {{ itemsForDate(day.date).length }}
                                </span>
                            </header>

                            <div v-if="itemsForDate(day.date).length > 0" class="agenda-week__items">
                                <article v-for="item in itemsForDate(day.date)" :key="`${item.type}-${item.id}`"
                                    class="agenda-week-item" :class="`agenda-week-item--${item.type}`"
                                    :data-testid="`agenda-week-item-${item.type}-${item.id}`">
                                    <div class="agenda-week-item__meta">
                                        <span class="agenda-week-item__dot"></span>

                                        <span class="agenda-week-item__type">
                                            {{ folderItemTypeLabel(
                                                item.type,
                                            {
                                            fallback: 'Item',
                                            preserveUnknown: false,
                                            },
                                            ) }}
                                        </span>
                                    </div>

                                    <span v-if="formatItemTime(item)" class="agenda-week-item__time">
                                        {{ formatItemTime(item) }}
                                    </span>

                                    <strong class="agenda-week-item__title">
                                        {{ item.title }}
                                    </strong>

                                    <span v-if="item.folder" class="agenda-week-item__folder">
                                        {{ item.folder.name }}
                                    </span>

                                    <span v-if="item.type === 'event' && item.location"
                                        class="agenda-week-item__location">
                                        {{ item.location }}
                                    </span>
                                </article>
                            </div>

                            <div v-else class="agenda-week__empty">
                                Nenhum item
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!--
        |--------------------------------------------------------------------------
        | Visualização em lista
        |--------------------------------------------------------------------------
        -->

            <section v-else-if="currentView === 'list'" data-testid="agenda-list" aria-label="Agenda em lista">
                <div v-if="agendaListGroups.length === 0" class="agenda-list__empty">
                    <div class="agenda-list__empty-mark" aria-hidden="true"></div>

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
                            <article v-for="item in group.items" :key="`${item.type}-${item.id}`"
                                class="agenda-list-item" :class="`agenda-list-item--${item.type}`"
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
                                            {{ folderItemTypeLabel(
                                                item.type,
                                                {
                                                    fallback: 'Item',
                                                    preserveUnknown: false,
                                                },
                                            ) }}
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
                                        {{ folderPriorityLabel(item.priority) }}
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>
            </section>
        </section>
    </PageContainer>
</template>

<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'

import { useRouter } from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'

import {
    addDays,
    formatDateKey,
    formatShortTime,
    isSameDate,
    parseDateKey,
} from '@/utils/date'

import {
    folderPriorityLabel,
    folderItemTypeLabel,
} from '@/constants/folder'

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

const currentFilter =
    ref('all')

const currentStatusFilter =
    ref('all')

const currentMonth =
    ref(
        new Date(
            todayReference.year,
            todayReference.month,
            1,
        ),
    )

const currentWeekStart =
    ref(
        startOfWeek(
            new Date(
                todayReference.year,
                todayReference.month,
                todayReference.day,
            ),
        ),
    )

const selectedDate =
    ref(
        formatDateKey(
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
        && view !== 'week'
        && view !== 'list'
    ) {
        return
    }

    if (
        view === 'week'
        && currentView.value !== 'week'
    ) {
        const referenceDate =
            parseDateKey(
                selectedDate.value,
            )
            ?? new Date(
                todayReference.year,
                todayReference.month,
                todayReference.day,
            )

        currentWeekStart.value =
            startOfWeek(
                referenceDate,
            )
    }

    currentView.value =
        view
}

/*
|--------------------------------------------------------------------------
| Filtros
|--------------------------------------------------------------------------
*/

const filteredItems =
    computed(() =>
        agendaStore.items.filter(
            (item) => {
                const matchesType =
                    currentFilter.value === 'all'
                    || item.type === currentFilter.value

                const matchesStatus =
                    matchesStatusFilter(
                        item,
                    )

                return (
                    matchesType
                    && matchesStatus
                )
            },
        ),
    )

function matchesStatusFilter(
    item,
) {
    if (
        currentStatusFilter.value === 'all'
    ) {
        return true
    }

    if (
        currentStatusFilter.value === 'completed'
    ) {
        return item.status === 'completed'
    }

    return (
        itemTemporalStatus(
            item,
        ) ===
        currentStatusFilter.value
    )
}

function itemTemporalStatus(
    item,
) {
    if (
        item.status === 'completed'
    ) {
        return 'completed'
    }

    const isPending =
        (
            item.type === 'event'
            && item.status === 'scheduled'
        )
        || (
            item.type !== 'event'
            && item.status === 'pending'
        )

    if (!isPending) {
        return null
    }

    const timestamp =
        Date.parse(
            item?.starts_at ?? '',
        )

    if (
        Number.isNaN(
            timestamp,
        )
    ) {
        return null
    }

    return (
        timestamp <
        today.getTime()
    )
        ? 'overdue'
        : 'pending'
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
| Semana
|--------------------------------------------------------------------------
*/

const currentPeriodLabel =
    computed(() => {
        if (
            currentView.value !== 'week'
        ) {
            return currentMonthLabel.value
        }

        const start =
            currentWeekStart.value

        const end =
            addDays(
                start,
                6,
            )

        const startDay =
            start.getDate()

        const endDay =
            end.getDate()

        if (
            start.getMonth() === end.getMonth()
            && start.getFullYear() === end.getFullYear()
        ) {
            return `${startDay}–${endDay} de ${monthNames[end.getMonth()]} de ${end.getFullYear()}`
        }

        return `${startDay} de ${monthNames[start.getMonth()]} – ${endDay} de ${monthNames[end.getMonth()]} de ${end.getFullYear()}`
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
                        formatDateKey(
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
| Grade semanal
|--------------------------------------------------------------------------
*/

const weekDays =
    computed(() => {
        return Array.from(
            {
                length: 7,
            },

            (
                _,
                index,
            ) => {
                const date =
                    addDays(
                        currentWeekStart.value,
                        index,
                    )

                return {
                    date:
                        formatDateKey(
                            date,
                        ),

                    day:
                        date.getDate(),

                    weekday:
                        weekdays[
                        index
                        ],

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
| Agrupamento
|--------------------------------------------------------------------------
*/

const itemsByDate =
    computed(() => {
        const grouped = {}

        for (
            const item of
            filteredItems.value
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
    computed(() =>
        Object
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
            ),
    )

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
| Carregamento
|--------------------------------------------------------------------------
*/

async function loadCurrentMonth() {
    errorMessage.value = ''

    const year =
        currentMonth.value.getFullYear()

    const month =
        currentMonth.value.getMonth()

    const start =
        formatDateKey(
            new Date(
                year,
                month,
                1,
            ),
        )

    const end =
        formatDateKey(
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
| Navegação temporal
|--------------------------------------------------------------------------
*/

async function loadCurrentWeek() {
    errorMessage.value = ''

    const start =
        formatDateKey(
            currentWeekStart.value,
        )

    const end =
        formatDateKey(
            addDays(
                currentWeekStart.value,
                6,
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

async function goToPreviousMonth() {
    if (
        currentView.value === 'week'
    ) {
        currentWeekStart.value =
            addDays(
                currentWeekStart.value,
                -7,
            )

        await loadCurrentWeek()

        return
    }

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
    if (
        currentView.value === 'week'
    ) {
        currentWeekStart.value =
            addDays(
                currentWeekStart.value,
                7,
            )

        await loadCurrentWeek()

        return
    }

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
    selectedDate.value =
        formatDateKey(
            new Date(
                todayReference.year,
                todayReference.month,
                todayReference.day,
            ),
        )

    if (
        currentView.value === 'week'
    ) {
        currentWeekStart.value =
            startOfWeek(
                new Date(
                    todayReference.year,
                    todayReference.month,
                    todayReference.day,
                ),
            )

        await loadCurrentWeek()

        return
    }

    currentMonth.value =
        new Date(
            todayReference.year,
            todayReference.month,
            1,
        )

    await loadCurrentMonth()
}

function selectFirstDayOfCurrentMonth() {
    selectedDate.value =
        formatDateKey(
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
| Itens do calendário
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
        return item.status === 'scheduled'
    }

    return item.status === 'pending'
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

    return firstDate - secondDate
}

/*
|--------------------------------------------------------------------------
| Datas
|--------------------------------------------------------------------------
*/

function startOfWeek(
    date,
) {
    const result =
        new Date(
            date.getFullYear(),
            date.getMonth(),
            date.getDate(),
        )

    const offset =
        (
            result.getDay()
            + 6
        ) % 7

    result.setDate(
        result.getDate()
        - offset,
    )

    return result
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

    return formatShortTime(
        item.starts_at,
    )
}

</script>

<style scoped>
.agenda-page {
    --agenda-surface:
        var(--color-surface);

    --agenda-surface-soft:
        var(--color-surface-muted);

    --agenda-border:
        var(--color-border);

    --agenda-text:
        var(--color-text);

    --agenda-muted:
        var(--color-text-muted);

    --agenda-event:
        var(--color-highlight);

    --agenda-event-soft:
        var(--color-surface-highlight-soft);

    --agenda-task:
        var(--color-brand-secondary);

    --agenda-task-soft:
        var(--color-surface-secondary-soft);

    --agenda-deadline:
        var(--color-danger);

    --agenda-deadline-soft:
        var(--color-danger-soft);

    display: flex;
    flex-direction: column;
    gap: 1.15rem;

    min-width: 0;

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
}

.agenda-page__title {
    margin: 0;

    font-size:
        clamp(1.5rem, 2vw, 1.9rem);

    font-weight: 750;
    letter-spacing: -0.03em;
}

.agenda-page__description {
    margin: 0.25rem 0 0;

    color:
        var(--agenda-muted);

    font-size:
        0.9rem;
}

/*
|--------------------------------------------------------------------------
| Alerta
|--------------------------------------------------------------------------
*/

.agenda-page__alert {
    padding: 0.75rem 1rem;

    border:
        1px solid var(--color-danger);

    border-radius:
        0.65rem;

    color:
        var(--color-danger);

    background:
        var(--color-danger-soft);
}

/*
|--------------------------------------------------------------------------
| Toolbar
|--------------------------------------------------------------------------
*/

.agenda-toolbar {
    display: grid;

    grid-template-columns:
        minmax(max-content, 1fr) auto auto;

    align-items: end;

    gap: 1rem;
}

.agenda-toolbar__period {
    display: flex;
    align-items: center;
    gap: 1rem;

    min-width: 0;
}

.agenda-calendar__navigation {
    display: flex;
    align-items: center;
    gap: 0.35rem;

    flex: 0 0 auto;
}

.agenda-calendar__navigation-button,
.agenda-calendar__today-button {
    height: 2.5rem;

    border:
        1px solid var(--agenda-border);

    border-radius:
        0.6rem;

    color:
        var(--agenda-text);

    background:
        var(--agenda-surface);

    font: inherit;

    cursor:
        pointer;
}

.agenda-calendar__navigation-button {
    width: 2.5rem;

    padding: 0;

    font-size:
        1.15rem;
}

.agenda-calendar__today-button {
    padding: 0 0.85rem;

    font-size:
        0.8rem;

    font-weight:
        680;
}

.agenda-calendar__navigation-button:hover,
.agenda-calendar__today-button:hover {
    background:
        var(--color-surface-secondary-soft);
}

.agenda-calendar__title {
    min-width: 0;

    margin: 0;

    font-size:
        1.08rem;

    font-weight:
        730;

    white-space:
        nowrap;
}

/*
|--------------------------------------------------------------------------
| Selects
|--------------------------------------------------------------------------
*/

.agenda-toolbar__filters {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(10rem, 12.5rem));

    gap: 0.65rem;
}

.agenda-select-field {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;

    min-width: 0;
}

.agenda-select-field__label {
    color:
        var(--agenda-muted);

    font-size:
        0.68rem;

    font-weight:
        700;
}

.agenda-select-field__control {
    position: relative;

    min-width: 0;
}

.agenda-select-field__control::after {
    content: '';

    position: absolute;

    top: 50%;
    right: 0.85rem;

    width: 0.45rem;
    height: 0.45rem;

    border-right:
        1.5px solid var(--agenda-muted);

    border-bottom:
        1.5px solid var(--agenda-muted);

    transform:
        translateY(-70%) rotate(45deg);

    pointer-events:
        none;
}

.agenda-select-field__select {
    width: 100%;
    height: 2.5rem;

    padding:
        0 2.2rem 0 0.8rem;

    border:
        1px solid var(--agenda-border);

    border-radius:
        0.6rem;

    outline: none;

    color:
        var(--agenda-text);

    background:
        var(--agenda-surface);

    font: inherit;

    font-size:
        0.78rem;

    font-weight:
        600;

    appearance:
        none;

    cursor:
        pointer;

    transition:
        border-color 140ms ease,
        box-shadow 140ms ease,
        background 140ms ease;
}

.agenda-select-field__select:hover {
    border-color:
        color-mix(in srgb,
            var(--color-brand-secondary) 30%,
            var(--agenda-border));
}

.agenda-select-field__select:focus {
    border-color:
        var(--color-brand-secondary);

    box-shadow:
        0 0 0 3px color-mix(in srgb,
            var(--color-brand-secondary) 14%,
            transparent);
}

/*
|--------------------------------------------------------------------------
| Mês / Semana Lista
|--------------------------------------------------------------------------
*/

.agenda-page__view-switcher {
    display: inline-flex;
    align-items: center;

    height: 2.5rem;

    padding: 0.18rem;

    border:
        1px solid var(--agenda-border);

    border-radius:
        0.65rem;

    background:
        var(--agenda-surface-soft);
}

.agenda-page__view-button {
    min-width: 4.4rem;
    height: 2.05rem;

    padding: 0 0.75rem;

    border: 0;

    border-radius:
        0.48rem;

    color:
        var(--agenda-muted);

    background:
        transparent;

    font: inherit;

    font-size:
        0.76rem;

    font-weight:
        700;

    cursor:
        pointer;
}

.agenda-page__view-button--active {
    color:
        var(--color-brand-secondary);

    background:
        var(--agenda-surface);

    box-shadow:
        0 1px 4px rgb(70 55 35 / 9%);
}

/*
|--------------------------------------------------------------------------
| Semana
|--------------------------------------------------------------------------
*/

.agenda-week {
    min-width: 0;
}

.agenda-week__viewport {
    overflow: hidden;

    border:
        1px solid var(--agenda-border);

    border-radius:
        0.85rem;

    background:
        var(--agenda-surface);
}

.agenda-week__grid {
    display: grid;

    grid-template-columns:
        repeat(7,
            minmax(0, 1fr));
}

.agenda-week__day {
    min-width: 0;
    min-height: 26rem;

    border-right:
        1px solid var(--agenda-border);

    background:
        var(--agenda-surface);
}

.agenda-week__day:last-child {
    border-right: 0;
}

.agenda-week__day--today {
    background:
        var(--color-surface-secondary-soft);
}

.agenda-week__day-header {
    position: relative;

    display: flex;
    flex-direction: column;
    align-items: center;

    gap: 0.2rem;

    padding:
        0.75rem 0.45rem;

    border-bottom:
        1px solid var(--agenda-border);
}

.agenda-week__weekday {
    color:
        var(--agenda-muted);

    font-size:
        0.65rem;

    font-weight:
        750;

    text-transform:
        uppercase;

    letter-spacing:
        0.05em;
}

.agenda-week__day-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    width: 2rem;
    height: 2rem;

    border-radius:
        999px;

    font-size:
        0.9rem;

    font-weight:
        750;
}

.agenda-week__day--today .agenda-week__day-number {
    color:
        var(--color-on-brand-secondary);

    background:
        var(--color-brand-secondary);
}

.agenda-week__count {
    position: absolute;

    top: 0.65rem;
    right: 0.5rem;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width: 1.3rem;
    height: 1.3rem;

    padding:
        0 0.25rem;

    border-radius:
        999px;

    color:
        var(--color-brand-secondary);

    background:
        var(--color-surface-secondary-soft);

    font-size:
        0.62rem;

    font-weight:
        750;
}

.agenda-week__items {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;

    padding:
        0.65rem;
}

.agenda-week-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;

    min-width: 0;

    padding:
        0.65rem;

    border:
        1px solid var(--agenda-border);

    border-left-width:
        3px;

    border-radius:
        0.55rem;

    background:
        var(--color-surface);
}

.agenda-week-item--event {
    border-left-color:
        var(--agenda-event);
}

.agenda-week-item--task {
    border-left-color:
        var(--agenda-task);
}

.agenda-week-item--deadline {
    border-left-color:
        var(--agenda-deadline);
}

.agenda-week-item__meta {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.agenda-week-item__dot {
    width: 0.4rem;
    height: 0.4rem;

    border-radius:
        999px;
}

.agenda-week-item--event .agenda-week-item__dot {
    background:
        var(--agenda-event);
}

.agenda-week-item--task .agenda-week-item__dot {
    background:
        var(--agenda-task);
}

.agenda-week-item--deadline .agenda-week-item__dot {
    background:
        var(--agenda-deadline);
}

.agenda-week-item__type {
    color:
        var(--agenda-muted);

    font-size:
        0.58rem;

    font-weight:
        800;

    text-transform:
        uppercase;
}

.agenda-week-item__time {
    font-size:
        0.68rem;

    font-weight:
        750;
}

.agenda-week-item__title {
    font-size:
        0.76rem;

    line-height:
        1.35;
}

.agenda-week-item__folder,
.agenda-week-item__location {
    color:
        var(--agenda-muted);

    font-size:
        0.64rem;

    line-height:
        1.35;
}

.agenda-week__empty {
    padding:
        1.2rem 0.5rem;

    color:
        var(--agenda-muted);

    font-size:
        0.65rem;

    text-align:
        center;
}

/*
|--------------------------------------------------------------------------
| Workspace
|--------------------------------------------------------------------------
*/

.agenda-workspace {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr) minmax(18rem, 21rem);

    align-items: start;

    gap: 1rem;

    min-width: 0;
}

/*
|--------------------------------------------------------------------------
| Calendário
|--------------------------------------------------------------------------
*/

.agenda-calendar {
    min-width: 0;
}

.agenda-calendar__container {
    overflow: hidden;

    border:
        1px solid var(--agenda-border);

    border-radius:
        0.85rem;

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
        0.72rem 0.45rem;

    color:
        var(--agenda-muted);

    font-size:
        0.67rem;

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
    min-height: 7.5rem;

    padding: 0.55rem;

    overflow: hidden;

    border: 0;

    border-right:
        1px solid var(--agenda-border);

    border-bottom:
        1px solid var(--agenda-border);

    color:
        var(--agenda-text);

    background:
        var(--agenda-surface);

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
        var(--color-surface-secondary-soft);
}

.agenda-calendar__day--outside {
    color:
        var(--agenda-muted);

    background:
        var(--agenda-surface-soft);
}

.agenda-calendar__day--selected {
    background:
        var(--color-surface-secondary-soft);
}

.agenda-calendar__day--today {
    box-shadow:
        inset 0 0 0 2px var(--color-brand-secondary);
}

.agenda-calendar__day-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    min-height: 1.8rem;
}

.agenda-calendar__day-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width: 1.7rem;
    height: 1.7rem;

    border-radius:
        999px;

    font-size:
        0.8rem;

    font-weight:
        650;
}

.agenda-calendar__day--today .agenda-calendar__day-number {
    color:
        var(--color-on-brand-secondary);

    background:
        var(--color-brand-secondary);
}

.agenda-calendar__day-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width: 1.25rem;
    height: 1.25rem;

    padding: 0 0.28rem;

    border-radius:
        999px;

    color:
        var(--color-on-brand-secondary);

    background:
        var(--color-brand-secondary);

    font-size:
        0.6rem;

    font-weight:
        750;
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

    margin-top:
        0.38rem;
}

.agenda-calendar__item {
    display: flex;
    align-items: center;
    gap: 0.27rem;

    min-width: 0;

    padding:
        0.28rem 0.36rem;

    border-radius:
        0.35rem;

    font-size:
        0.63rem;
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

    width: 0.36rem;
    height: 0.36rem;

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

    font-weight:
        750;
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
        0.6rem;

    color:
        var(--color-brand-secondary);

    font-size:
        0.62rem;

    font-weight:
        700;
}

/*
|--------------------------------------------------------------------------
| Legenda
|--------------------------------------------------------------------------
*/

.agenda-calendar__legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;

    margin-top:
        0.7rem;

    color:
        var(--agenda-muted);

    font-size:
        0.7rem;
}

.agenda-calendar__legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.agenda-calendar__legend-dot {
    width: 0.42rem;
    height: 0.42rem;

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
| Painel lateral
|--------------------------------------------------------------------------
*/

.agenda-selected-day {
    position: sticky;

    top: 1rem;

    overflow: hidden;

    border:
        1px solid var(--agenda-border);

    border-radius:
        0.85rem;

    background:
        var(--agenda-surface);
}

.agenda-selected-day__header {
    padding: 1.1rem;

    border-bottom:
        1px solid var(--agenda-border);
}

.agenda-selected-day__weekday {
    display: block;

    margin-bottom:
        0.35rem;

    color:
        var(--color-brand-secondary);

    font-size:
        0.66rem;

    font-weight:
        800;

    letter-spacing:
        0.05em;
}

.agenda-selected-day__title {
    margin: 0;

    font-size:
        1.12rem;

    font-weight:
        740;
}

.agenda-selected-day__summary {
    margin-top:
        0.65rem;

    color:
        var(--agenda-muted);

    font-size:
        0.75rem;
}

.agenda-selected-day__items {
    padding: 1rem;
}

.agenda-selected-day__empty {
    display: flex;
    flex-direction: column;
    align-items: center;

    gap: 0.5rem;

    min-height:
        15rem;

    padding:
        2rem 1.4rem;

    color:
        var(--agenda-muted);

    text-align:
        center;
}

.agenda-selected-day__empty-mark,
.agenda-list__empty-mark {
    width: 2.5rem;
    height: 2.5rem;

    border:
        2px solid var(--color-brand-secondary);

    border-radius:
        50%;

    opacity:
        0.3;
}

/*
|--------------------------------------------------------------------------
| Item selecionado
|--------------------------------------------------------------------------
*/

.agenda-selected-item {
    padding-bottom:
        0.9rem;
}

.agenda-selected-item+.agenda-selected-item {
    margin-top:
        0.9rem;

    padding-top:
        0.9rem;

    border-top:
        1px solid var(--agenda-border);
}

.agenda-selected-item__type {
    display: flex;
    align-items: center;
    gap: 0.38rem;

    margin-bottom:
        0.5rem;

    font-size:
        0.65rem;

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
    width: 0.42rem;
    height: 0.42rem;

    border-radius:
        999px;

    background:
        currentColor;
}

.agenda-selected-item__card {
    padding:
        0.9rem;

    border:
        1px solid var(--agenda-border);

    border-left-width:
        3px;

    border-radius:
        0.6rem;

    background:
        var(--color-surface);
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
    gap: 0.55rem;
}

.agenda-selected-item__time {
    font-size:
        0.7rem;

    font-weight:
        750;
}

.agenda-selected-item__title {
    font-size:
        0.86rem;
}

.agenda-selected-item__details {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;

    margin:
        0.75rem 0 0;

    padding: 0;
}

.agenda-selected-item__detail {
    display: grid;

    grid-template-columns:
        4.5rem minmax(0, 1fr);

    gap: 0.55rem;

    font-size:
        0.72rem;
}

.agenda-selected-item__detail dt {
    color:
        var(--agenda-muted);
}

.agenda-selected-item__detail dd {
    display: flex;
    flex-direction: column;

    min-width: 0;

    margin: 0;
}

.agenda-selected-item__process {
    color:
        var(--agenda-muted);

    font-size:
        0.68rem;

    overflow-wrap:
        anywhere;
}

.agenda-selected-item__priority {
    width:
        fit-content;

    padding:
        0.14rem 0.36rem;

    border-radius:
        999px;
}

.agenda-selected-item__priority--high {
    background:
        var(--agenda-deadline-soft);
}

.agenda-selected-item__priority--medium {
    background:
        var(--color-surface-warning-soft);
}

.agenda-selected-item__priority--low {
    background:
        var(--agenda-task-soft);
}

.agenda-selected-item__actions-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;

    margin-top:
        0.85rem;
}

.agenda-selected-item__folder-button,
.agenda-selected-item__complete-button {
    min-height: 2rem;

    padding:
        0.3rem 0.58rem;

    border-radius:
        0.55rem;

    font:
        inherit;

    font-size:
        0.69rem;

    font-weight:
        650;

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
        1px solid var(--color-brand-secondary);

    color:
        var(--color-brand-secondary);

    background:
        var(--color-surface-secondary-soft);
}

/*
|--------------------------------------------------------------------------
| Totais
|--------------------------------------------------------------------------
*/

.agenda-selected-day__totals {
    padding: 0.9rem;

    border-top:
        1px solid var(--agenda-border);
}

.agenda-selected-day__totals-title {
    margin:
        0 0 0.6rem;

    font-size:
        0.72rem;
}

.agenda-selected-day__totals-grid {
    display: grid;

    grid-template-columns:
        repeat(3,
            minmax(0, 1fr));

    gap: 0.4rem;
}

.agenda-selected-day__total {
    display: flex;
    flex-direction: column;
    align-items: center;

    padding:
        0.55rem 0.25rem;

    border:
        1px solid var(--agenda-border);

    border-radius:
        0.55rem;
}

.agenda-selected-day__total-value {
    font-weight:
        800;
}

.agenda-selected-day__total-label {
    color:
        var(--agenda-muted);

    font-size:
        0.56rem;
}

/*
|--------------------------------------------------------------------------
| Lista
|--------------------------------------------------------------------------
*/

.agenda-list {
    width: 100%;

    overflow: hidden;

    border:
        1px solid var(--agenda-border);

    border-radius:
        0.85rem;

    background:
        var(--agenda-surface);
}

.agenda-list__group+.agenda-list__group {
    border-top:
        1px solid var(--agenda-border);
}

.agenda-list__group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 1rem;

    padding:
        0.9rem 1.1rem;

    background:
        var(--agenda-surface-soft);
}

.agenda-list__weekday {
    display: block;

    margin-bottom:
        0.15rem;

    color:
        var(--color-brand-secondary);

    font-size:
        0.62rem;

    font-weight:
        800;

    letter-spacing:
        0.05em;
}

.agenda-list__date {
    margin: 0;

    font-size:
        0.94rem;

    font-weight:
        720;
}

.agenda-list__count {
    padding:
        0.22rem 0.5rem;

    border-radius:
        999px;

    color:
        var(--agenda-muted);

    background:
        var(--agenda-surface);

    font-size:
        0.66rem;

    font-weight:
        700;
}

.agenda-list-item {
    display: grid;

    grid-template-columns:
        4rem 0.7rem minmax(0, 1fr);

    gap: 0.7rem;

    align-items:
        start;

    padding:
        0.9rem 1.1rem;
}

.agenda-list-item+.agenda-list-item {
    border-top:
        1px solid var(--agenda-border);
}

.agenda-list-item__time {
    color:
        var(--agenda-muted);

    font-size:
        0.74rem;

    font-weight:
        700;
}

.agenda-list-item__marker {
    display: flex;
    justify-content: center;

    padding-top:
        0.3rem;
}

.agenda-list-item__dot {
    width: 0.48rem;
    height: 0.48rem;

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

.agenda-list-item__heading {
    display: flex;
    align-items: baseline;
    gap: 0.6rem;
}

.agenda-list-item__type {
    font-size:
        0.64rem;

    font-weight:
        800;

    text-transform:
        uppercase;
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
        0.85rem;
}

.agenda-list-item__folder {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;

    margin-top:
        0.3rem;

    color:
        var(--agenda-muted);

    font-size:
        0.72rem;
}

.agenda-list-item__process::before {
    content:
        '•';

    margin-right:
        0.4rem;
}

.agenda-list-item__meta {
    margin-top:
        0.2rem;

    color:
        var(--agenda-muted);

    font-size:
        0.7rem;
}

.agenda-list__empty {
    display: flex;
    flex-direction: column;
    align-items: center;

    gap: 0.5rem;

    padding:
        3.5rem 1.5rem;

    color:
        var(--agenda-muted);

    text-align:
        center;
}

/*
|--------------------------------------------------------------------------
| Tablet
|--------------------------------------------------------------------------
*/

@media (max-width: 1180px) {
    .agenda-toolbar {
        grid-template-columns:
            1fr auto;
    }

    .agenda-toolbar__filters {
        grid-column:
            1;

        grid-row:
            2;

        justify-self:
            start;
    }

    .agenda-page__view-switcher {
        grid-column:
            2;

        grid-row:
            1 / span 2;

        align-self:
            end;
    }

    .agenda-workspace {
        grid-template-columns:
            1fr;
    }

    .agenda-selected-day {
        position:
            static;

        width:
            100%;
    }
}

/*
|--------------------------------------------------------------------------
| Tablet estreito
|--------------------------------------------------------------------------
*/

@media (max-width: 820px) {
    .agenda-toolbar {
        grid-template-columns:
            1fr;
    }

    .agenda-toolbar__period,
    .agenda-toolbar__filters,
    .agenda-page__view-switcher {
        grid-column:
            1;

        grid-row:
            auto;
    }

    .agenda-toolbar__period {
        justify-content:
            space-between;
    }

    .agenda-toolbar__filters {
        width:
            100%;

        grid-template-columns:
            repeat(2,
                minmax(0, 1fr));
    }

    .agenda-page__view-switcher {
        width:
            100%;
    }

    .agenda-page__view-button {
        flex:
            1 1 0;
    }

    .agenda-calendar__container {
        overflow-x:
            auto;
    }

    .agenda-calendar__weekdays,
    .agenda-calendar__grid {
        min-width:
            45rem;
    }
}

/*
|--------------------------------------------------------------------------
| Mobile
|--------------------------------------------------------------------------
*/

@media (max-width: 560px) {
    .agenda-page {
        gap:
            1rem;
    }

    .agenda-page__title {
        font-size:
            1.4rem;
    }

    .agenda-page__description {
        font-size:
            0.82rem;

        line-height:
            1.45;
    }

    .agenda-toolbar {
        gap:
            0.8rem;
    }

    .agenda-toolbar__period {
        display: grid;

        grid-template-columns:
            1fr;

        gap:
            0.65rem;
    }

    .agenda-calendar__title {
        grid-row:
            1;

        text-align:
            center;

        font-size:
            1rem;
    }

    .agenda-calendar__navigation {
        grid-row:
            2;

        display: grid;

        grid-template-columns:
            2.6rem 1fr 2.6rem;

        width:
            100%;
    }

    .agenda-calendar__navigation-button,
    .agenda-calendar__today-button {
        width:
            100%;
    }

    .agenda-toolbar__filters {
        grid-template-columns:
            1fr;

        gap:
            0.55rem;
    }

    .agenda-select-field__label {
        font-size:
            0.66rem;
    }

    .agenda-select-field__select {
        height:
            2.7rem;

        font-size:
            0.8rem;
    }

    .agenda-page__view-switcher {
        height:
            2.7rem;
    }

    .agenda-page__view-button {
        height:
            2.25rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Calendário mobile
    |--------------------------------------------------------------------------
    |
    | Não comprimimos as sete colunas.
    | Mantemos largura mínima e permitimos scroll horizontal.
    |
    */

    .agenda-calendar__weekdays,
    .agenda-calendar__grid {
        min-width:
            42rem;
    }

    .agenda-calendar__day {
        min-height:
            6.6rem;

        padding:
            0.45rem;
    }

    .agenda-calendar__legend {
        justify-content:
            flex-start;

        overflow-x:
            auto;

        padding-bottom:
            0.2rem;
    }

    .agenda-week__grid {
        display: grid;

        grid-template-columns:
            repeat(7,
                minmax(10rem, 1fr));

        min-width:
            max-content;
    }

    /*
    |--------------------------------------------------------------------------
    | Semana mobile
    |--------------------------------------------------------------------------
    */

    .agenda-week__day {
        min-height:
            20rem;
    }

    .agenda-week__viewport {
        overflow-x:
            auto;

        overscroll-behavior-inline:
            contain;
    }

    /*
    |--------------------------------------------------------------------------
    | Painel do dia
    |--------------------------------------------------------------------------
    */

    .agenda-selected-day__header,
    .agenda-selected-day__items,
    .agenda-selected-day__totals {
        padding:
            0.85rem;
    }

    .agenda-selected-item__detail {
        grid-template-columns:
            1fr;

        gap:
            0.15rem;
    }

    .agenda-selected-item__actions-row {
        flex-direction:
            column;
    }

    .agenda-selected-item__folder-button,
    .agenda-selected-item__complete-button {
        width:
            100%;
    }

    /*
    |--------------------------------------------------------------------------
    | Semana mobile
    |--------------------------------------------------------------------------
    */

    @media (max-width: 1180px) {
        .agenda-week__viewport {
            overflow-x:
                auto;
        }

        .agenda-week__grid {
            min-width:
                58rem;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Lista mobile
    |--------------------------------------------------------------------------
    */

    .agenda-list-item {
        grid-template-columns:
            0.7rem minmax(0, 1fr);

        gap:
            0.55rem;

        padding:
            0.85rem;
    }

    .agenda-list-item__time {
        grid-column:
            1 / -1;

        font-size:
            0.7rem;
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
        flex-direction:
            column;

        align-items:
            flex-start;

        gap:
            0.2rem;
    }

    .agenda-list__group-header {
        padding:
            0.8rem;
    }
}
</style>