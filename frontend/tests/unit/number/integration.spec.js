import { describe, expect, it } from 'vitest'

import {
  processNumber,
} from '@/components/forms/shared/number/index.js'

describe('processNumber', () => {
  it('processa uma entrada pt-BR completa', () => {
    const result = processNumber('R$ 1.234,567')

    expect(result).toEqual({
      sanitized: '1234,567',
      parsed: 1234.567,
      rounded: 1234.57,
      clamped: 1234.57,
      formatted: '1.234,57',
    })
  })

  it('aplica o limite máximo', () => {
    const result = processNumber('5000', {
      max: 1000,
    })

    expect(result.parsed).toBe(5000)
    expect(result.clamped).toBe(1000)
    expect(result.formatted).toBe('1.000')
  })

  it('aplica o limite mínimo', () => {
    const result = processNumber('-500', {
      min: 0,
    })

    expect(result.parsed).toBe(-500)
    expect(result.clamped).toBe(0)
    expect(result.formatted).toBe('0')
  })

  it('respeita a precisão configurada', () => {
    const result = processNumber('1,23456', {
      precision: 3,
    })

    expect(result.rounded).toBe(1.235)
    expect(result.formatted).toBe('1,235')
  })

  it('aceita configuração en-US', () => {
    const result = processNumber('1,234.567', {
      decimalSeparator: '.',
      groupSeparator: ',',
      locale: 'en-US',
      precision: 2,
    })

    expect(result.sanitized).toBe('1234.567')
    expect(result.parsed).toBe(1234.567)
    expect(result.rounded).toBe(1234.57)
    expect(result.formatted).toBe('1,234.57')
  })

  it('remove o sinal quando negativos não são permitidos', () => {
    const result = processNumber('-123,45', {
      allowNegative: false,
    })

    expect(result.sanitized).toBe('123,45')
    expect(result.parsed).toBe(123.45)
  })

  it('retorna estados vazios para entrada inválida', () => {
    const result = processNumber('abc')

    expect(result).toEqual({
      sanitized: '',
      parsed: null,
      rounded: null,
      clamped: null,
      formatted: '',
    })
  })

  it('retorna estados vazios para entrada incompleta', () => {
    const result = processNumber('-,')

    expect(result).toEqual({
      sanitized: '-,',
      parsed: null,
      rounded: null,
      clamped: null,
      formatted: '',
    })
  })

  it('permite definir quantidade mínima de casas decimais', () => {
    const result = processNumber('12', {
      precision: 2,
      minimumFractionDigits: 2,
    })

    expect(result.formatted).toBe('12,00')
  })

  it('permite desabilitar agrupamento', () => {
    const result = processNumber('1234,56', {
      useGrouping: false,
    })

    expect(result.formatted).toBe('1234,56')
  })
})