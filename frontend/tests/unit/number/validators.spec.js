import { describe, expect, it } from 'vitest'

import {
  clampNumber,
  isFiniteNumber,
  isStepAligned,
  isWithinMax,
  isWithinMin,
  isWithinRange,
  roundToPrecision,
} from '@/components/forms/shared/number/index.js'

describe('Number Engine validators', () => {
  describe('isFiniteNumber', () => {
    it('aceita apenas números finitos', () => {
      expect(isFiniteNumber(0)).toBe(true)
      expect(isFiniteNumber(-10)).toBe(true)
      expect(isFiniteNumber(1.5)).toBe(true)

      expect(isFiniteNumber('10')).toBe(false)
      expect(isFiniteNumber(null)).toBe(false)
      expect(isFiniteNumber(Number.NaN)).toBe(false)
      expect(isFiniteNumber(Number.POSITIVE_INFINITY)).toBe(false)
    })
  })

  describe('isWithinMin', () => {
    it('valida o limite mínimo', () => {
      expect(isWithinMin(5, 5)).toBe(true)
      expect(isWithinMin(10, 5)).toBe(true)
      expect(isWithinMin(4, 5)).toBe(false)
    })

    it('aceita ausência de limite', () => {
      expect(isWithinMin(10, undefined)).toBe(true)
      expect(isWithinMin(10, null)).toBe(true)
    })

    it('rejeita valor não numérico quando há limite', () => {
      expect(isWithinMin('10', 5)).toBe(false)
      expect(isWithinMin(Number.NaN, 5)).toBe(false)
    })
  })

  describe('isWithinMax', () => {
    it('valida o limite máximo', () => {
      expect(isWithinMax(5, 5)).toBe(true)
      expect(isWithinMax(4, 5)).toBe(true)
      expect(isWithinMax(6, 5)).toBe(false)
    })

    it('aceita ausência de limite', () => {
      expect(isWithinMax(10, undefined)).toBe(true)
      expect(isWithinMax(10, null)).toBe(true)
    })

    it('rejeita valor não numérico quando há limite', () => {
      expect(isWithinMax('10', 20)).toBe(false)
      expect(isWithinMax(Number.NaN, 20)).toBe(false)
    })
  })

  describe('isWithinRange', () => {
    it('valida os dois limites', () => {
      expect(isWithinRange(5, { min: 0, max: 10 })).toBe(true)
      expect(isWithinRange(0, { min: 0, max: 10 })).toBe(true)
      expect(isWithinRange(10, { min: 0, max: 10 })).toBe(true)

      expect(isWithinRange(-1, { min: 0, max: 10 })).toBe(false)
      expect(isWithinRange(11, { min: 0, max: 10 })).toBe(false)
    })

    it('aceita configuração vazia', () => {
      expect(isWithinRange(5)).toBe(true)
    })
  })

  describe('clampNumber', () => {
    it('limita ao mínimo', () => {
      expect(clampNumber(-5, { min: 0 })).toBe(0)
    })

    it('limita ao máximo', () => {
      expect(clampNumber(20, { max: 10 })).toBe(10)
    })

    it('mantém valores dentro do intervalo', () => {
      expect(clampNumber(5, { min: 0, max: 10 })).toBe(5)
    })

    it('aplica ambos os limites', () => {
      expect(clampNumber(-20, { min: -10, max: 10 })).toBe(-10)
      expect(clampNumber(20, { min: -10, max: 10 })).toBe(10)
    })

    it('retorna null para valor inválido', () => {
      expect(clampNumber(null)).toBeNull()
      expect(clampNumber('10')).toBeNull()
      expect(clampNumber(Number.NaN)).toBeNull()
    })
  })

  describe('isStepAligned', () => {
    it('valida step inteiro', () => {
      expect(isStepAligned(10, 5)).toBe(true)
      expect(isStepAligned(12, 5)).toBe(false)
    })

    it('valida step decimal', () => {
      expect(isStepAligned(1.5, 0.5)).toBe(true)
      expect(isStepAligned(1.6, 0.5)).toBe(false)
    })

    it('considera uma origem personalizada', () => {
      expect(isStepAligned(15, 10, 5)).toBe(true)
      expect(isStepAligned(16, 10, 5)).toBe(false)
    })

    it('aceita step any ou ausente', () => {
      expect(isStepAligned(1.37, 'any')).toBe(true)
      expect(isStepAligned(1.37, undefined)).toBe(true)
      expect(isStepAligned(1.37, null)).toBe(true)
    })

    it('rejeita configurações inválidas', () => {
      expect(isStepAligned(10, 0)).toBe(false)
      expect(isStepAligned(10, -1)).toBe(false)
      expect(isStepAligned(10, 'abc')).toBe(false)
      expect(isStepAligned(10, 1, Number.NaN)).toBe(false)
    })

    it('rejeita valor inválido', () => {
      expect(isStepAligned(Number.NaN, 1)).toBe(false)
      expect(isStepAligned('10', 1)).toBe(false)
    })
  })

  describe('roundToPrecision', () => {
    it('arredonda para a precisão indicada', () => {
      expect(roundToPrecision(1.2345, 2)).toBe(1.23)
      expect(roundToPrecision(1.2355, 2)).toBe(1.24)
      expect(roundToPrecision(10.999, 0)).toBe(11)
    })

    it('normaliza precisões inválidas para zero', () => {
      expect(roundToPrecision(10.6, -1)).toBe(11)
      expect(roundToPrecision(10.6, 1.5)).toBe(11)
      expect(roundToPrecision(10.6, 'abc')).toBe(11)
    })

    it('aceita precisão numérica em string', () => {
      expect(roundToPrecision(1.2345, '2')).toBe(1.23)
    })

    it('retorna null para valores inválidos', () => {
      expect(roundToPrecision(null, 2)).toBeNull()
      expect(roundToPrecision('1.23', 2)).toBeNull()
      expect(roundToPrecision(Number.NaN, 2)).toBeNull()
    })
  })
})