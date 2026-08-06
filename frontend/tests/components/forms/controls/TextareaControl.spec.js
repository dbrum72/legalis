import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import TextareaControl from '@/components/forms/controls/TextareaControl/index.vue'

describe('TextareaControl', () => {
    function mountComponent(props = {}) {
        return mount(TextareaControl, {
            props: {
                modelValue: 'Texto inicial',
                id: 'description',
                name: 'description',
                ...props,
            },
        })
    }

    it('renderiza o textarea', () => {
        const wrapper = mountComponent()

        const textarea = wrapper.get('textarea')

        expect(textarea.exists()).toBe(true)
        expect(textarea.attributes('id')).toBe('description')
        expect(textarea.attributes('name')).toBe('description')
        expect(textarea.element.value).toBe('Texto inicial')
    })

    it('aplica quatro linhas por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('textarea').attributes('rows')).toBe('4')
    })

    it('encaminha placeholder e autocomplete', () => {
        const wrapper = mountComponent({
            placeholder: 'Digite uma descrição...',
            autocomplete: 'off',
        })

        const textarea = wrapper.get('textarea')

        expect(textarea.attributes('placeholder')).toBe('Digite uma descrição...')

        expect(textarea.attributes('autocomplete')).toBe('off')
    })

    it('encaminha maxlength e minlength', () => {
        const wrapper = mountComponent({
            maxlength: 500,
            minlength: 10,
        })

        const textarea = wrapper.get('textarea')

        expect(textarea.attributes('maxlength')).toBe('500')
        expect(textarea.attributes('minlength')).toBe('10')
    })

    it('encaminha rows, cols e wrap', () => {
        const wrapper = mountComponent({
            rows: 8,
            cols: 60,
            wrap: 'hard',
        })

        const textarea = wrapper.get('textarea')

        expect(textarea.attributes('rows')).toBe('8')
        expect(textarea.attributes('cols')).toBe('60')
        expect(textarea.attributes('wrap')).toBe('hard')
    })

    it('utiliza wrap soft por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('textarea').attributes('wrap')).toBe('soft')
    })

    it('encaminha disabled, readonly e required', () => {
        const wrapper = mountComponent({
            disabled: true,
            readonly: true,
            required: true,
        })

        const textarea = wrapper.get('textarea')

        expect(textarea.attributes()).toHaveProperty('disabled')
        expect(textarea.attributes()).toHaveProperty('readonly')
        expect(textarea.attributes()).toHaveProperty('required')
    })

    it('encaminha autofocus', () => {
        const wrapper = mountComponent({
            autofocus: true,
        })

        expect(wrapper.get('textarea').attributes()).toHaveProperty('autofocus')
    })

    it('emite update:modelValue durante a edição', async () => {
        const wrapper = mountComponent()

        const textarea = wrapper.get('textarea')

        await textarea.setValue('Novo conteúdo')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates).toBeTruthy()
        expect(updates.at(-1)).toEqual(['Novo conteúdo'])
    })

    it('emite string quando modelValue inicial é numérico', async () => {
        const wrapper = mountComponent({
            modelValue: 123,
        })

        const textarea = wrapper.get('textarea')

        await textarea.setValue('456')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual(['456'])
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const textarea = wrapper.get('textarea')

        await textarea.trigger('focus')
        await textarea.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })
})
