import { describe, expect, it } from 'vitest'

import { pick } from '@/components/forms/shared/utils/pick.js'

describe('pick', () => {
    it('retorna apenas as propriedades solicitadas', () => {
        const source = {
            id: 'email',
            disabled: true,
            required: false,
            label: 'E-mail',
        }

        expect(pick(source, ['id', 'label'])).toEqual({
            id: 'email',
            label: 'E-mail',
        })
    })

    it('ignora propriedades inexistentes', () => {
        const source = {
            id: 'email',
        }

        expect(pick(source, ['id', 'placeholder'])).toEqual({
            id: 'email',
        })
    })

    it('preserva valores false', () => {
        const source = {
            disabled: false,
        }

        expect(pick(source, ['disabled'])).toEqual({
            disabled: false,
        })
    })

    it('preserva valores zero', () => {
        const source = {
            min: 0,
        }

        expect(pick(source, ['min'])).toEqual({
            min: 0,
        })
    })

    it('preserva string vazia', () => {
        const source = {
            placeholder: '',
        }

        expect(pick(source, ['placeholder'])).toEqual({
            placeholder: '',
        })
    })

    it('preserva valores null', () => {
        const source = {
            modelValue: null,
        }

        expect(pick(source, ['modelValue'])).toEqual({
            modelValue: null,
        })
    })

    it('preserva propriedades undefined quando existentes', () => {
        const source = {
            maxlength: undefined,
        }

        const result = pick(source, ['maxlength'])

        expect(Object.prototype.hasOwnProperty.call(result, 'maxlength')).toBe(true)

        expect(result.maxlength).toBeUndefined()
    })

    it('não altera o objeto original', () => {
        const source = {
            id: 'email',
            disabled: true,
        }

        const clone = { ...source }

        pick(source, ['id'])

        expect(source).toEqual(clone)
    })

    it('retorna objeto vazio quando nenhuma chave existe', () => {
        expect(
            pick(
                {
                    id: 'email',
                },
                ['foo', 'bar'],
            ),
        ).toEqual({})
    })

    it('mantém a ordem definida na lista de chaves', () => {
        const result = pick(
            {
                a: 1,
                b: 2,
                c: 3,
            },
            ['c', 'a'],
        )

        expect(Object.keys(result)).toEqual(['c', 'a'])
    })

    it('retorna um novo objeto', () => {
        const source = {
            id: 'email',
        }

        const result = pick(source, ['id'])

        expect(result).not.toBe(source)
    })

    it('retorna objeto vazio quando a lista de chaves está vazia', () => {
        expect(
            pick(
                {
                    id: 'email',
                },
                [],
            ),
        ).toEqual({})
    })
})
