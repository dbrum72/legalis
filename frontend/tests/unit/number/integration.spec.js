import { describe, expect, it } from 'vitest'

import {
  clampNumber,
  formatNumber,
  parseNumber,
  roundToPrecision,
  sanitizeNumberInput,
} from '@/components/forms/shared/number'

describe('Number Engine integration', () => {
  function process(value, options = {}) {
    const sanitized = sanitizeNumberInput(value, options)

    const parsed = parseNumber(sanitized, options)

    if (parsed === null) {
      return {
        sanitized,
        parsed,
        rounded: null,
        clamped: null,
        formatted: '',
      }
    }

    const rounded = roundToPrecision(
      parsed,
      options.precision ?? 2,
    )

    const clamped = clampNumber(rounded, {
      min: options.min,
      max: options.max,
    })

    return {
      sanitized,
      parsed,
      rounded,
      clamped,
      formatted: formatNumber(clamped, {
        locale: options.locale,
        maximumFractionDigits:
          options.precision ?? 2,
        minimumFractionDigits: 0,
      }),
    }
  }

  it('processa uma entrada pt-BR completa', () => {
    const result = process('R$ 1.234,567')

    expect(result.sanitized).toBe('1234,567')
    expect(result.parsed).toBe(1234.567)
    expect(result.rounded).toBe(1234.57)
    expect(result.clamped).toBe(1234.57)
    expect(result.formatted).toBe('1.234,57')
  })

  it('aplica limites', () => {
    const result = process('5000', {
      max: 1000,
    })

    expect(result.clamped).toBe(1000)
    expect(result.formatted).toBe('1.000')
  })

  it('aceita locale en-US', () => {
    const result = process('1,234.567', {
      decimalSeparator: '.',
      groupSeparator: ',',
      locale: 'en-US',
      precision: 2,
    })

    expect(result.parsed).toBe(1234.567)
    expect(result.formatted).toBe('1,234.57')
  })

  it('retorna resultado vazio para entrada inválida', () => {
    const result = process('abc')

    expect(result.parsed).toBeNull()
    expect(result.formatted).toBe('')
  })

  it('processa negativos', () => {
    const result = process('-1.234,56')

    expect(result.parsed).toBe(-1234.56)
    expect(result.formatted).toBe('-1.234,56')
  })
})