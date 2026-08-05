import { describe, expect, it } from 'vitest'

import {
  formatNumber,
} from '@/components/forms/shared/number/index.js'

describe('formatNumber', () => {
  describe('contrato da API', () => {
    it('sempre retorna uma string', () => {
      expect(typeof formatNumber(0)).toBe('string')
      expect(typeof formatNumber(123.45)).toBe('string')
      expect(typeof formatNumber(null)).toBe('string')
      expect(typeof formatNumber(undefined)).toBe('string')
    })

    it('não lança exceção para entradas inválidas', () => {
      expect(() => formatNumber(undefined)).not.toThrow()
      expect(() => formatNumber(null)).not.toThrow()
      expect(() => formatNumber(Number.NaN)).not.toThrow()
      expect(() => formatNumber(Number.POSITIVE_INFINITY)).not.toThrow()
      expect(() => formatNumber(Number.NEGATIVE_INFINITY)).not.toThrow()
    })
  })

  describe('valores vazios e inválidos', () => {
    it('retorna string vazia', () => {
      expect(formatNumber(null)).toBe('')
      expect(formatNumber(undefined)).toBe('')
      expect(formatNumber('')).toBe('')
      expect(formatNumber(Number.NaN)).toBe('')
      expect(formatNumber(Number.POSITIVE_INFINITY)).toBe('')
      expect(formatNumber(Number.NEGATIVE_INFINITY)).toBe('')
    })

    it('retorna string vazia para texto não numérico', () => {
      expect(formatNumber('abc')).toBe('')
    })
  })

  describe('formatação padrão pt-BR', () => {
    it('formata números inteiros', () => {
      expect(formatNumber(0)).toBe('0')
      expect(formatNumber(123)).toBe('123')
      expect(formatNumber(1234)).toBe('1.234')
      expect(formatNumber(1234567)).toBe('1.234.567')
    })

    it('formata números decimais', () => {
      expect(formatNumber(1.5)).toBe('1,5')
      expect(formatNumber(1234.56)).toBe('1.234,56')
      expect(formatNumber(0.25)).toBe('0,25')
    })

    it('formata números negativos', () => {
      expect(formatNumber(-123)).toBe('-123')
      expect(formatNumber(-1234.56)).toBe('-1.234,56')
    })
  })

  describe('casas decimais', () => {
    it('respeita minimumFractionDigits', () => {
      expect(
        formatNumber(12, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }),
      ).toBe('12,00')
    })

    it('respeita maximumFractionDigits', () => {
      expect(
        formatNumber(1.23456, {
          maximumFractionDigits: 3,
        }),
      ).toBe('1,235')
    })

    it('eleva o máximo quando ele é menor que o mínimo', () => {
      expect(
        formatNumber(12.3, {
          minimumFractionDigits: 3,
          maximumFractionDigits: 1,
        }),
      ).toBe('12,300')
    })

    it('normaliza precisões inválidas', () => {
      expect(
        formatNumber(12.345, {
          minimumFractionDigits: -1,
          maximumFractionDigits: -5,
        }),
      ).toBe('12')
    })

    it('limita a precisão ao máximo aceito', () => {
      expect(() =>
        formatNumber(1.25, {
          minimumFractionDigits: 30,
          maximumFractionDigits: 30,
        }),
      ).not.toThrow()
    })
  })

  describe('locale e agrupamento', () => {
    it('formata usando en-US', () => {
      expect(
        formatNumber(1234.56, {
          locale: 'en-US',
        }),
      ).toBe('1,234.56')
    })

    it('desabilita o agrupamento', () => {
      expect(
        formatNumber(1234.56, {
          useGrouping: false,
        }),
      ).toBe('1234,56')
    })
  })

  describe('coerção de valores', () => {
    it('aceita strings numéricas não localizadas', () => {
      expect(formatNumber('1234.56')).toBe('1.234,56')
    })

    it('não interpreta strings localizadas diretamente', () => {
      expect(formatNumber('1.234,56')).toBe('')
    })
  })

  describe('valores grandes e pequenos', () => {
    it('formata valores grandes', () => {
      expect(formatNumber(987654321.99))
        .toBe('987.654.321,99')
    })

    it('formata valores decimais pequenos', () => {
      expect(
        formatNumber(0.0001, {
          maximumFractionDigits: 4,
        }),
      ).toBe('0,0001')
    })
  })
})