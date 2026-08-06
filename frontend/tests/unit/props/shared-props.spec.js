import { describe, expect, it } from 'vitest'

import { commonProps } from '@/components/forms/shared/props/common.js'
import { controlProps } from '@/components/forms/shared/props/control.js'
import { fieldProps } from '@/components/forms/shared/props/field.js'

describe('shared form props', () => {
    describe('commonProps', () => {
        it('define o contrato comum esperado', () => {
            expect(Object.keys(commonProps)).toEqual(['id', 'disabled', 'required'])
        })

        it('define id como string opcional', () => {
            expect(commonProps.id.type).toBe(String)
            expect(commonProps.id.default).toBeUndefined()
        })

        it('define disabled como boolean false por padrão', () => {
            expect(commonProps.disabled.type).toBe(Boolean)
            expect(commonProps.disabled.default).toBe(false)
        })

        it('define required como boolean false por padrão', () => {
            expect(commonProps.required.type).toBe(Boolean)
            expect(commonProps.required.default).toBe(false)
        })
    })

    describe('controlProps', () => {
        it('inclui todas as props comuns', () => {
            expect(controlProps).toMatchObject(commonProps)
        })

        it('define o contrato adicional esperado', () => {
            expect(Object.keys(controlProps)).toEqual([
                'id',
                'disabled',
                'required',
                'name',
                'autofocus',
            ])
        })

        it('define name como string opcional', () => {
            expect(controlProps.name.type).toBe(String)
            expect(controlProps.name.default).toBeUndefined()
        })

        it('define autofocus como boolean false por padrão', () => {
            expect(controlProps.autofocus.type).toBe(Boolean)
            expect(controlProps.autofocus.default).toBe(false)
        })

        it('não reutiliza a referência raiz de commonProps', () => {
            expect(controlProps).not.toBe(commonProps)
        })

        it('reutiliza os descritores das props comuns', () => {
            expect(controlProps.id).toBe(commonProps.id)
            expect(controlProps.disabled).toBe(commonProps.disabled)
            expect(controlProps.required).toBe(commonProps.required)
        })
    })

    describe('fieldProps', () => {
        it('inclui todas as props comuns', () => {
            expect(fieldProps).toMatchObject(commonProps)
        })

        it('define o contrato adicional esperado', () => {
            expect(Object.keys(fieldProps)).toEqual([
                'id',
                'disabled',
                'required',
                'readonly',
                'label',
                'hint',
                'error',
            ])
        })

        it('define readonly como boolean false por padrão', () => {
            expect(fieldProps.readonly.type).toBe(Boolean)
            expect(fieldProps.readonly.default).toBe(false)
        })

        it('define label como string vazia por padrão', () => {
            expect(fieldProps.label.type).toBe(String)
            expect(fieldProps.label.default).toBe('')
        })

        it('define hint como string vazia por padrão', () => {
            expect(fieldProps.hint.type).toBe(String)
            expect(fieldProps.hint.default).toBe('')
        })

        it('define error como string vazia por padrão', () => {
            expect(fieldProps.error.type).toBe(String)
            expect(fieldProps.error.default).toBe('')
        })

        it('não reutiliza a referência raiz de commonProps', () => {
            expect(fieldProps).not.toBe(commonProps)
        })

        it('reutiliza os descritores das props comuns', () => {
            expect(fieldProps.id).toBe(commonProps.id)
            expect(fieldProps.disabled).toBe(commonProps.disabled)
            expect(fieldProps.required).toBe(commonProps.required)
        })
    })

    describe('consistência entre contratos', () => {
        it('mantém as props comuns com os mesmos descritores', () => {
            const commonKeys = Object.keys(commonProps)

            commonKeys.forEach((key) => {
                expect(controlProps[key]).toBe(commonProps[key])
                expect(fieldProps[key]).toBe(commonProps[key])
            })
        })

        it('mantém responsabilidades específicas separadas', () => {
            expect(controlProps).not.toHaveProperty('label')
            expect(controlProps).not.toHaveProperty('hint')
            expect(controlProps).not.toHaveProperty('error')
            expect(controlProps).not.toHaveProperty('readonly')

            expect(fieldProps).not.toHaveProperty('name')
            expect(fieldProps).not.toHaveProperty('autofocus')
        })

        it('não compartilha a mesma referência entre fieldProps e controlProps', () => {
            expect(fieldProps).not.toBe(controlProps)
        })

        it('não modifica commonProps durante a composição', () => {
            expect(Object.keys(commonProps)).toEqual(['id', 'disabled', 'required'])
        })
    })
})
