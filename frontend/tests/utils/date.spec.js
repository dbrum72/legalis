import { describe, expect, it } from 'vitest'

import {
    addDays,
    formatDateKey,
    formatDayMonth,
    formatShortDate,
    formatShortDateTime,
    formatShortTime,
    formatWeekday,
    isSameDate,
    parseDateKey,
} from '@/utils/date'

describe('date utils', () => {
    it('formata data curta em pt-BR', () => {
        const date = new Date(2026, 7, 25, 14, 30)

        expect(formatShortDate(date)).toBe('25/08/2026')
    })

    it('formata data e hora curtas em pt-BR', () => {
        const date = new Date(2026, 7, 25, 14, 30)

        expect(formatShortDateTime(date)).toContain('25/08/2026')

        expect(formatShortDateTime(date)).toContain('14:30')
    })

    it('formata somente a hora', () => {
        const date = new Date(2026, 7, 25, 14, 30)

        expect(formatShortTime(date)).toBe('14:30')
    })

    it('retorna travessao para data vazia', () => {
        expect(formatShortDate(null)).toBe('—')

        expect(formatShortDateTime(null)).toBe('—')
    })

    it('preserva valor invalido nos formatadores de data', () => {
        expect(formatShortDate('valor inválido')).toBe('valor inválido')

        expect(formatShortDateTime('valor inválido')).toBe('valor inválido')
    })

    it('retorna vazio para horario invalido', () => {
        expect(formatShortTime('valor inválido')).toBe('')
    })

    it('adiciona dias preservando calendario local', () => {
        const date = new Date(2026, 7, 25)

        expect(addDays(date, 2)).toEqual(new Date(2026, 7, 27))
    })

    it('formata chave de data local', () => {
        const date = new Date(2026, 7, 5)

        expect(formatDateKey(date)).toBe('2026-08-05')
    })

    it('converte chave de data local para Date', () => {
        expect(parseDateKey('2026-08-25')).toEqual(new Date(2026, 7, 25))
    })

    it('retorna null para chave de data invalida', () => {
        expect(parseDateKey('')).toBeNull()

        expect(parseDateKey('invalida')).toBeNull()
    })

    it('compara datas ignorando horario', () => {
        const first = new Date(2026, 7, 25, 9, 30)

        const second = new Date(2026, 7, 25, 18, 45)

        const third = new Date(2026, 7, 26, 9, 30)

        expect(isSameDate(first, second)).toBe(true)

        expect(isSameDate(first, third)).toBe(false)
    })

    it('formata dia e mes', () => {
        const date = new Date(2026, 7, 5)

        expect(formatDayMonth(date)).toBe('05/08')
    })

    it('formata dia da semana por extenso', () => {
        const date = new Date(2026, 7, 25)

        expect(formatWeekday(date)).toBe('terça-feira')
    })

    it('normaliza valores vazios e invalidos nos formatadores compactos', () => {
        expect(formatDayMonth(null)).toBe('')

        expect(formatDayMonth('inválida')).toBe('')

        expect(formatWeekday(null)).toBe('')

        expect(formatWeekday('inválida')).toBe('')
    })
})
