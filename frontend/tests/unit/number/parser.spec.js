import { describe, expect, it } from 'vitest'

import {
  parseNumber,
} from '@/components/forms/shared/number/index.js'

describe('parseNumber', () => {
  describe('valores vazios ou incompletos', () => {
    it('retorna null para entradas ausentes', () => {
      expect(parseNumber(null)).toBeNull()
      expect(parseNumber(undefined)).toBeNull()
      expect(parseNumber('')).toBeNull()
    })

    it('retorna null para sinais ou separadores incompletos', () => {
      expect(parseNumber('-')).toBeNull()
      expect(parseNumber(',')).toBeNull()
      expect(parseNumber('-,')).toBeNull()
    })
  })

  describe('valores inteiros', () => {
    it('converte inteiros positivos', () => {
      expect(parseNumber('0')).toBe(0)
      expect(parseNumber('123')).toBe(123)
      expect(parseNumber(456)).toBe(456)
    })

    it('converte inteiros negativos', () => {
      expect(parseNumber('-123')).toBe(-123)
    })

    it('remove separadores de agrupamento', () => {
      expect(parseNumber('1.234')).toBe(1234)
      expect(parseNumber('1.234.567')).toBe(1234567)
    })
  })

  describe('valores decimais', () => {
    it('converte decimais no padrão pt-BR', () => {
      expect(parseNumber('1,5')).toBe(1.5)
      expect(parseNumber('1234,56')).toBe(1234.56)
      expect(parseNumber('1.234,56')).toBe(1234.56)
    })

    it('converte decimais negativos', () => {
      expect(parseNumber('-1.234,56')).toBe(-1234.56)
    })

    it('aceita zero decimal', () => {
      expect(parseNumber('0,00')).toBe(0)
      expect(parseNumber('-0,00')).toBe(-0)
    })
  })

  describe('configuração de locale', () => {
    it('aceita padrão en-US', () => {
      expect(
        parseNumber('1,234.56', {
          decimalSeparator: '.',
          groupSeparator: ',',
        }),
      ).toBe(1234.56)
    })

    it('aceita separador decimal sem agrupamento', () => {
      expect(
        parseNumber('1234.56', {
          decimalSeparator: '.',
          groupSeparator: '',
        }),
      ).toBe(1234.56)
    })

    it('aceita separadores personalizados', () => {
      expect(
        parseNumber('1 234;56', {
          decimalSeparator: ';',
          groupSeparator: ' ',
        }),
      ).toBe(1234.56)
    })
  })

  describe('valores negativos', () => {
    it('remove o sinal quando negativos não são permitidos', () => {
      expect(
        parseNumber('-123,45', {
          allowNegative: false,
        }),
      ).toBe(123.45)
    })

    it('ignora sinal negativo fora do início', () => {
      expect(parseNumber('12-3')).toBe(123)
    })
  })

  describe('entradas com caracteres adicionais', () => {
    it('remove símbolos e texto', () => {
      expect(parseNumber('R$ 1.234,56')).toBe(1234.56)
      expect(parseNumber('abc123,45xyz')).toBe(123.45)
    })

    it('preserva apenas o primeiro separador decimal', () => {
      expect(parseNumber('1,,23')).toBe(1.23)
      expect(parseNumber('12,3,4')).toBe(12.34)
    })
  })

  describe('entradas inválidas', () => {
    it('retorna null quando não há nenhum dígito', () => {
      expect(parseNumber('abc')).toBeNull()
      expect(parseNumber('R$')).toBeNull()
    })

    it('retorna null para resultados não finitos', () => {
      expect(parseNumber(Number.NaN)).toBeNull()
      expect(parseNumber(Number.POSITIVE_INFINITY)).toBeNull()
      expect(parseNumber(Number.NEGATIVE_INFINITY)).toBeNull()
    })
  })

  describe('precisão', () => {
    it('não arredonda o valor convertido', () => {
      expect(parseNumber('1,234567')).toBe(1.234567)
    })

    it('preserva casas decimais significativas', () => {
      expect(parseNumber('0,0001')).toBe(0.0001)
    })
  })
})