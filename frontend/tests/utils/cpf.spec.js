import { describe, expect, it } from 'vitest'

import { isValidCpf } from '@/utils/cpf.js'

describe('isValidCpf', () => {
    it.each([
        '52998224725',
        '529.982.247-25',
    ])('aceita o CPF válido %s', (cpf) => {
        expect(isValidCpf(cpf)).toBe(true)
    })

    it.each([
        '52998224724',
        '11111111111',
        '12345678901',
        '',
    ])('rejeita o CPF inválido %s', (cpf) => {
        expect(isValidCpf(cpf)).toBe(false)
    })
})
