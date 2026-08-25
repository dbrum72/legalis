import {
    FOLDER_DEADLINE_STATUS_LABELS,
    FOLDER_EVENT_STATUS_LABELS,
    FOLDER_EVENT_TYPE_LABELS,
    FOLDER_PRIORITY_LABELS,
    FOLDER_TASK_STATUS_LABELS,
    folderDeadlineStatusLabel,
    folderEventStatusLabel,
    folderEventTypeLabel,
    folderPriorityLabel,
    folderTaskStatusLabel,
    FOLDER_ITEM_TYPE_LABELS,
    folderItemTypeLabel,
} from '@/constants/folder'

describe('folder constants', () => {
    describe('folderEventTypeLabel', () => {
        it.each([
            ['hearing', 'Audiência'],
            ['meeting', 'Reunião'],
            ['expert_exam', 'Perícia'],
            ['diligence', 'Diligência'],
            ['other', 'Outro'],
        ])('converte %s para %s', (value, expected) => {
            expect(folderEventTypeLabel(value)).toBe(expected)
        })

        it('mantém tipo desconhecido', () => {
            expect(folderEventTypeLabel('custom')).toBe('custom')
        })

        it('retorna travessão quando não há tipo', () => {
            expect(folderEventTypeLabel(null)).toBe('—')
        })
    })

    describe('folderPriorityLabel', () => {
        it.each([
            ['high', 'Alta'],
            ['medium', 'Média'],
            ['low', 'Baixa'],
        ])('converte %s para %s', (value, expected) => {
            expect(folderPriorityLabel(value)).toBe(expected)
        })

        it('mantém prioridade desconhecida', () => {
            expect(folderPriorityLabel('custom')).toBe('custom')
        })

        it('retorna travessão quando não há prioridade', () => {
            expect(folderPriorityLabel(null)).toBe('—')
        })
    })

    it('expõe os mapas canônicos do domínio', () => {
        expect(FOLDER_EVENT_TYPE_LABELS).toEqual({
            hearing: 'Audiência',
            meeting: 'Reunião',
            expert_exam: 'Perícia',
            diligence: 'Diligência',
            other: 'Outro',
        })

        expect(FOLDER_PRIORITY_LABELS).toEqual({
            high: 'Alta',
            medium: 'Média',
            low: 'Baixa',
        })
    })

    describe('folderTaskStatusLabel', () => {
        it.each([
            ['pending', 'Pendente'],
            ['completed', 'Concluído'],
        ])('converte %s para %s', (value, expected) => {
            expect(folderTaskStatusLabel(value)).toBe(expected)
        })

        it('mantém status desconhecido', () => {
            expect(folderTaskStatusLabel('custom')).toBe('custom')
        })

        it('retorna travessão sem status', () => {
            expect(folderTaskStatusLabel(null)).toBe('—')
        })
    })

    describe('folderDeadlineStatusLabel', () => {
        it.each([
            ['pending', 'Pendente'],
            ['completed', 'Concluído'],
            ['cancelled', 'Cancelado'],
        ])('converte %s para %s', (value, expected) => {
            expect(folderDeadlineStatusLabel(value)).toBe(expected)
        })

        it('mantém status desconhecido', () => {
            expect(folderDeadlineStatusLabel('custom')).toBe('custom')
        })

        it('retorna travessão sem status', () => {
            expect(folderDeadlineStatusLabel(null)).toBe('—')
        })
    })

    describe('folderEventStatusLabel', () => {
        it.each([
            ['scheduled', 'Agendado'],
            ['completed', 'Concluído'],
            ['cancelled', 'Cancelado'],
        ])('converte %s para %s', (value, expected) => {
            expect(folderEventStatusLabel(value)).toBe(expected)
        })

        it('mantém status desconhecido', () => {
            expect(folderEventStatusLabel('custom')).toBe('custom')
        })

        it('retorna travessão sem status', () => {
            expect(folderEventStatusLabel(null)).toBe('—')
        })
    })

    it('expõe os mapas de status por entidade', () => {
        expect(FOLDER_TASK_STATUS_LABELS).toEqual({
            pending: 'Pendente',
            completed: 'Concluído',
        })

        expect(FOLDER_DEADLINE_STATUS_LABELS).toEqual({
            pending: 'Pendente',
            completed: 'Concluído',
            cancelled: 'Cancelado',
        })

        expect(FOLDER_EVENT_STATUS_LABELS).toEqual({
            scheduled: 'Agendado',
            completed: 'Concluído',
            cancelled: 'Cancelado',
        })
    })

    describe('folderItemTypeLabel', () => {
        it.each([
            ['task', 'Tarefa'],
            ['deadline', 'Prazo'],
            ['event', 'Compromisso'],
        ])('converte %s para %s', (value, expected) => {
            expect(folderItemTypeLabel(value)).toBe(expected)
        })

        it('mantém tipo desconhecido por padrão', () => {
            expect(folderItemTypeLabel('custom')).toBe('custom')
        })

        it('retorna travessão quando não há tipo', () => {
            expect(folderItemTypeLabel(null)).toBe('—')
        })

        it('permite fallback específico sem preservar tipo desconhecido', () => {
            expect(
                folderItemTypeLabel('custom', {
                    fallback: 'Item',
                    preserveUnknown: false,
                }),
            ).toBe('Item')

            expect(
                folderItemTypeLabel(null, {
                    fallback: 'Item',
                    preserveUnknown: false,
                }),
            ).toBe('Item')
        })

        it('expõe o mapa canônico de tipos de item', () => {
            expect(FOLDER_ITEM_TYPE_LABELS).toEqual({
                task: 'Tarefa',
                deadline: 'Prazo',
                event: 'Compromisso',
            })
        })
    })
})
