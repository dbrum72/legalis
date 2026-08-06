import { describe, expect, it } from 'vitest'

import {
    AUTOCOMPLETE_CONTROL_KEYS,
    CHECKBOX_CONTROL_KEYS,
    INPUT_CONTROL_KEYS,
    RADIO_CONTROL_KEYS,
    SELECT_CONTROL_KEYS,
    SWITCH_CONTROL_KEYS,
    TEXTAREA_CONTROL_KEYS,
} from '@/components/forms/shared/constants/control-keys.js'

describe('control-keys', () => {
    it('não contém chaves duplicadas em INPUT_CONTROL_KEYS', () => {
        expect(new Set(INPUT_CONTROL_KEYS).size).toBe(INPUT_CONTROL_KEYS.length)
    })

    it('não contém chaves duplicadas em TEXTAREA_CONTROL_KEYS', () => {
        expect(new Set(TEXTAREA_CONTROL_KEYS).size).toBe(TEXTAREA_CONTROL_KEYS.length)
    })

    it('não contém chaves duplicadas em SELECT_CONTROL_KEYS', () => {
        expect(new Set(SELECT_CONTROL_KEYS).size).toBe(SELECT_CONTROL_KEYS.length)
    })

    it('não contém chaves duplicadas em AUTOCOMPLETE_CONTROL_KEYS', () => {
        expect(new Set(AUTOCOMPLETE_CONTROL_KEYS).size).toBe(AUTOCOMPLETE_CONTROL_KEYS.length)
    })

    it('não contém chaves duplicadas nos controles de seleção', () => {
        expect(new Set(CHECKBOX_CONTROL_KEYS).size).toBe(CHECKBOX_CONTROL_KEYS.length)

        expect(new Set(RADIO_CONTROL_KEYS).size).toBe(RADIO_CONTROL_KEYS.length)

        expect(new Set(SWITCH_CONTROL_KEYS).size).toBe(SWITCH_CONTROL_KEYS.length)
    })

    it('inclui as chaves essenciais dos controles', () => {
        const collections = [
            INPUT_CONTROL_KEYS,
            TEXTAREA_CONTROL_KEYS,
            SELECT_CONTROL_KEYS,
            AUTOCOMPLETE_CONTROL_KEYS,
            CHECKBOX_CONTROL_KEYS,
            RADIO_CONTROL_KEYS,
            SWITCH_CONTROL_KEYS,
        ]

        collections.forEach((keys) => {
            expect(keys).toContain('modelValue')
            expect(keys).toContain('id')
            expect(keys).toContain('name')
            expect(keys).toContain('disabled')
            expect(keys).toContain('required')
            expect(keys).toContain('autofocus')
        })
    })

    it('INPUT_CONTROL_KEYS contém atributos próprios do input', () => {
        expect(INPUT_CONTROL_KEYS).toEqual(
            expect.arrayContaining([
                'type',
                'placeholder',
                'readonly',
                'autocomplete',
                'maxlength',
                'minlength',
                'inputmode',
                'min',
                'max',
                'step',
            ]),
        )
    })

    it('TEXTAREA_CONTROL_KEYS contém atributos próprios do textarea', () => {
        expect(TEXTAREA_CONTROL_KEYS).toEqual(
            expect.arrayContaining([
                'placeholder',
                'readonly',
                'autocomplete',
                'maxlength',
                'minlength',
                'rows',
                'cols',
                'wrap',
            ]),
        )
    })

    it('SELECT_CONTROL_KEYS contém configuração de opções', () => {
        expect(SELECT_CONTROL_KEYS).toEqual(
            expect.arrayContaining(['placeholder', 'options', 'optionLabel', 'optionValue']),
        )

        expect(SELECT_CONTROL_KEYS).not.toContain('readonly')
    })

    it('AUTOCOMPLETE_CONTROL_KEYS contém os dois modelos e configurações de pesquisa', () => {
        expect(AUTOCOMPLETE_CONTROL_KEYS).toEqual(
            expect.arrayContaining([
                'modelValue',
                'searchValue',
                'options',
                'optionLabel',
                'optionValue',
                'noResultsText',
                'minSearchLength',
                'openOnFocus',
                'clearable',
            ]),
        )
    })

    it('CHECKBOX_CONTROL_KEYS contém value e indeterminate', () => {
        expect(CHECKBOX_CONTROL_KEYS).toEqual(expect.arrayContaining(['value', 'indeterminate']))
    })

    it('RADIO_CONTROL_KEYS contém value', () => {
        expect(RADIO_CONTROL_KEYS).toContain('value')
        expect(RADIO_CONTROL_KEYS).not.toContain('indeterminate')
    })

    it('SWITCH_CONTROL_KEYS contém apenas o contrato necessário', () => {
        expect(SWITCH_CONTROL_KEYS).toEqual([
            'modelValue',
            'id',
            'name',
            'disabled',
            'required',
            'autofocus',
        ])
    })

    it('não compartilha a mesma referência entre coleções', () => {
        expect(INPUT_CONTROL_KEYS).not.toBe(TEXTAREA_CONTROL_KEYS)

        expect(SELECT_CONTROL_KEYS).not.toBe(AUTOCOMPLETE_CONTROL_KEYS)

        expect(CHECKBOX_CONTROL_KEYS).not.toBe(RADIO_CONTROL_KEYS)
    })
})
