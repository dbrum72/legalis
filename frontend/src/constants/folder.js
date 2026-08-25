export const FOLDER_EVENT_TYPE_LABELS = {
    hearing: 'Audiência',
    meeting: 'Reunião',
    expert_exam: 'Perícia',
    diligence: 'Diligência',
    other: 'Outro',
}

export const FOLDER_PRIORITY_LABELS = {
    high: 'Alta',
    medium: 'Média',
    low: 'Baixa',
}

export function folderEventTypeLabel(type) {
    return FOLDER_EVENT_TYPE_LABELS[type] ?? type ?? '—'
}

export function folderPriorityLabel(priority) {
    return FOLDER_PRIORITY_LABELS[priority] ?? priority ?? '—'
}

export const FOLDER_TASK_STATUS_LABELS = {
    pending: 'Pendente',
    completed: 'Concluído',
}

export const FOLDER_DEADLINE_STATUS_LABELS = {
    pending: 'Pendente',
    completed: 'Concluído',
    cancelled: 'Cancelado',
}

export const FOLDER_EVENT_STATUS_LABELS = {
    scheduled: 'Agendado',
    completed: 'Concluído',
    cancelled: 'Cancelado',
}

export function folderTaskStatusLabel(status) {
    return FOLDER_TASK_STATUS_LABELS[status] ?? status ?? '—'
}

export function folderDeadlineStatusLabel(status) {
    return FOLDER_DEADLINE_STATUS_LABELS[status] ?? status ?? '—'
}

export function folderEventStatusLabel(status) {
    return FOLDER_EVENT_STATUS_LABELS[status] ?? status ?? '—'
}

export const FOLDER_ITEM_TYPE_LABELS = {
    task: 'Tarefa',
    deadline: 'Prazo',
    event: 'Compromisso',
}

export function folderItemTypeLabel(type, { fallback = '—', preserveUnknown = true } = {}) {
    if (type && Object.prototype.hasOwnProperty.call(FOLDER_ITEM_TYPE_LABELS, type)) {
        return FOLDER_ITEM_TYPE_LABELS[type]
    }

    if (preserveUnknown && type) {
        return type
    }

    return fallback
}
