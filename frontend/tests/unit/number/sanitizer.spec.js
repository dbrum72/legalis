import { describe, expect, it } from 'vitest'

import {
  sanitizeNumberInput,
} from '@/components/forms/shared/number/index.js'

describe('sanitizeNumberInput', () => {
  it('retorna string vazia para valores ausentes', () => {
    expect(sanitizeNumberInput(null)).toBe('')
    expect(sanitizeNumberInput(undefined)).toBe('')
  })

  it('remove caracteres não numéricos', () => {
    expect(sanitizeNumberInput('abc123')).toBe('123')
    expect(sanitizeNumberInput('R$ 1.234,56')).toBe('1234,56')
  })

  it('preserva apenas o primeiro separador decimal', () => {
    expect(sanitizeNumberInput('1,,23')).toBe('1,23')
    expect(sanitizeNumberInput('12,3,4')).toBe('12,34')
  })

  it('preserva o sinal negativo somente no início', () => {
    expect(sanitizeNumberInput('-1.234,56')).toBe('-1234,56')
    expect(sanitizeNumberInput('1-23')).toBe('123')
  })

  it('remove o sinal negativo quando allowNegative é false', () => {
    expect(
      sanitizeNumberInput('-123', {
        allowNegative: false,
      }),
    ).toBe('123')
  })

  it('aceita separador decimal personalizado', () => {
    expect(
      sanitizeNumberInput('1,234.56', {
        decimalSeparator: '.',
      }),
    ).toBe('1234.56')
  })

  it('mantém entrada decimal incompleta durante a digitação', () => {
    expect(sanitizeNumberInput(',')).toBe(',')
    expect(sanitizeNumberInput('-,')).toBe('-,')
  })
})