import { describe, expect, it } from 'vitest'

import { applyValidationErrors, clearValidationErrors } from '@/utils/validationErrors'

describe('validationErrors utils', () => {
    describe('clearValidationErrors', () => {
        it('limpa todos os campos do objeto de erros', () => {
            const errors = {
                name: 'Informe o nome.',
                email: 'Informe o e-mail.',
            }

            clearValidationErrors(errors)

            expect(errors).toEqual({
                name: '',
                email: '',
            })
        })

        it('mantém a estrutura original do objeto', () => {
            const errors = {
                name: 'Erro',
                document: 'Erro',
                address: 'Erro',
            }

            const result = clearValidationErrors(errors)

            expect(Object.keys(errors)).toEqual(['name', 'document', 'address'])

            expect(result).toBe(errors)
        })

        it('aceita objeto vazio', () => {
            const errors = {}

            expect(clearValidationErrors(errors)).toBe(errors)

            expect(errors).toEqual({})
        })
    })

    describe('applyValidationErrors', () => {
        it('aplica a primeira mensagem de cada campo', () => {
            const errors = {
                name: '',
                email: '',
            }

            applyValidationErrors(errors, {
                name: ['Nome inválido.', 'Segunda mensagem.'],

                email: ['E-mail inválido.'],
            })

            expect(errors).toEqual({
                name: 'Nome inválido.',
                email: 'E-mail inválido.',
            })
        })

        it('limpa campos sem erro retornado pelo backend', () => {
            const errors = {
                name: 'Erro anterior',
                email: 'Erro anterior',
            }

            applyValidationErrors(errors, {
                name: ['Nome inválido.'],
            })

            expect(errors).toEqual({
                name: 'Nome inválido.',
                email: '',
            })
        })

        it('ignora campos do backend que não existem no objeto local', () => {
            const errors = {
                name: '',
            }

            applyValidationErrors(errors, {
                name: ['Nome inválido.'],

                unknown_field: ['Erro desconhecido.'],
            })

            expect(errors).toEqual({
                name: 'Nome inválido.',
            })
        })

        it('aceita ausência de erros de validação', () => {
            const errors = {
                name: 'Erro anterior',
            }

            applyValidationErrors(errors)

            expect(errors).toEqual({
                name: '',
            })
        })

        it('preserva a identidade do objeto de erros', () => {
            const errors = {
                name: '',
            }

            const result = applyValidationErrors(errors, {
                name: ['Nome inválido.'],
            })

            expect(result).toBe(errors)
        })
    })
})
