import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import InputControl from '@/components/forms/controls/InputControl/index.vue'

describe('InputControl', () => {
    function mountComponent(props = {}) {
        return mount(InputControl, {
            props: {
                modelValue: 'Texto inicial',
                id: 'name',
                name: 'name',
                ...props,
            },
        })
    }

    it('renderiza o input', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.exists()).toBe(true)
        expect(input.attributes('id')).toBe('name')
        expect(input.attributes('name')).toBe('name')
        expect(input.element.value).toBe('Texto inicial')
    })

    it('utiliza type text por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('input').attributes('type')).toBe('text')
    })

    it('encaminha type e inputmode', () => {
        const wrapper = mountComponent({
            type: 'email',
            inputmode: 'email',
        })

        const input = wrapper.get('input')

        expect(input.attributes('type')).toBe('email')
        expect(input.attributes('inputmode')).toBe('email')
    })

    it('encaminha placeholder e autocomplete', () => {
        const wrapper = mountComponent({
            placeholder: 'Digite seu nome',
            autocomplete: 'name',
        })

        const input = wrapper.get('input')

        expect(input.attributes('placeholder')).toBe('Digite seu nome')

        expect(input.attributes('autocomplete')).toBe('name')
    })

    it('encaminha maxlength e minlength', () => {
        const wrapper = mountComponent({
            maxlength: 120,
            minlength: 3,
        })

        const input = wrapper.get('input')

        expect(input.attributes('maxlength')).toBe('120')
        expect(input.attributes('minlength')).toBe('3')
    })

    it('encaminha min, max e step', () => {
        const wrapper = mountComponent({
            type: 'number',
            min: 0,
            max: 100,
            step: 0.5,
        })

        const input = wrapper.get('input')

        expect(input.attributes('min')).toBe('0')
        expect(input.attributes('max')).toBe('100')
        expect(input.attributes('step')).toBe('0.5')
    })

    it('encaminha disabled, readonly e required', () => {
        const wrapper = mountComponent({
            disabled: true,
            readonly: true,
            required: true,
        })

        const input = wrapper.get('input')

        expect(input.attributes()).toHaveProperty('disabled')
        expect(input.attributes()).toHaveProperty('readonly')
        expect(input.attributes()).toHaveProperty('required')
    })

    it('encaminha autofocus', () => {
        const wrapper = mountComponent({
            autofocus: true,
        })

        expect(wrapper.get('input').attributes()).toHaveProperty('autofocus')
    })

    it('emite update:modelValue durante a edição', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.setValue('Novo valor')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates).toBeTruthy()
        expect(updates.at(-1)).toEqual(['Novo valor'])
    })

    it('emite string mesmo para input number', async () => {
        const wrapper = mountComponent({
            modelValue: 10,
            type: 'number',
        })

        const input = wrapper.get('input')

        await input.setValue('25')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual(['25'])
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })
})
