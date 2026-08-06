import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import RadioControl from '@/components/forms/controls/RadioControl/index.vue'

describe('RadioControl', () => {
    function mountComponent(props = {}) {
        return mount(RadioControl, {
            props: {
                modelValue: null,
                value: 'active',
                id: 'status-active',
                name: 'status',
                ...props,
            },
        })
    }

    it('renderiza o radio', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('type')).toBe('radio')
        expect(input.attributes('id')).toBe('status-active')
        expect(input.attributes('name')).toBe('status')
        expect(input.attributes('value')).toBe('active')
    })

    it('permanece desmarcado quando modelValue é diferente de value', () => {
        const wrapper = mountComponent({
            modelValue: 'inactive',
            value: 'active',
        })

        expect(wrapper.get('input').element.checked).toBe(false)
    })

    it('fica marcado quando modelValue é igual a value', () => {
        const wrapper = mountComponent({
            modelValue: 'active',
            value: 'active',
        })

        expect(wrapper.get('input').element.checked).toBe(true)
    })

    it('usa comparação estrita para valores numéricos', () => {
        const wrapper = mountComponent({
            modelValue: 1,
            value: 1,
        })

        expect(wrapper.get('input').element.checked).toBe(true)
    })

    it('não considera string e number como valores iguais', () => {
        const wrapper = mountComponent({
            modelValue: '1',
            value: 1,
        })

        expect(wrapper.get('input').element.checked).toBe(false)
    })

    it('preserva valores booleanos na seleção', () => {
        const wrapper = mountComponent({
            modelValue: false,
            value: false,
        })

        expect(wrapper.get('input').element.checked).toBe(true)
    })

    it('emite o valor string configurado', async () => {
        const wrapper = mountComponent({
            value: 'inactive',
        })

        await wrapper.get('input').trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([['inactive']])
    })

    it('emite o valor numérico configurado', async () => {
        const wrapper = mountComponent({
            value: 20,
        })

        await wrapper.get('input').trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([[20]])
    })

    it('emite o valor booleano configurado', async () => {
        const wrapper = mountComponent({
            value: false,
        })

        await wrapper.get('input').trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([[false]])
    })

    it('encaminha disabled, required e autofocus', () => {
        const wrapper = mountComponent({
            disabled: true,
            required: true,
            autofocus: true,
        })

        const input = wrapper.get('input')

        expect(input.attributes()).toHaveProperty('disabled')
        expect(input.attributes()).toHaveProperty('required')
        expect(input.attributes()).toHaveProperty('autofocus')
    })

    it('encaminha aria-invalid', () => {
        const wrapper = mountComponent({
            ariaInvalid: 'true',
        })

        expect(wrapper.get('input').attributes('aria-invalid')).toBe('true')
    })

    it('encaminha aria-describedby', () => {
        const wrapper = mountComponent({
            ariaDescribedBy: 'status-error',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('status-error')
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('reage à alteração de modelValue', async () => {
        const wrapper = mountComponent({
            modelValue: 'inactive',
            value: 'active',
        })

        const input = wrapper.get('input')

        expect(input.element.checked).toBe(false)

        await wrapper.setProps({
            modelValue: 'active',
        })

        expect(input.element.checked).toBe(true)
    })

    it('reage à alteração de value', async () => {
        const wrapper = mountComponent({
            modelValue: 10,
            value: 20,
        })

        const input = wrapper.get('input')

        expect(input.element.checked).toBe(false)

        await wrapper.setProps({
            value: 10,
        })

        expect(input.element.checked).toBe(true)
    })
})
