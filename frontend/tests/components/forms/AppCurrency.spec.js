import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppCurrency from '@/components/forms/variants/AppCurrency/index.vue'

describe('AppCurrency', () => {
    function mountComponent(props = {}) {
        return mount(AppCurrency, {
            props: {
                modelValue: 1234.56,
                id: 'amount',
                label: 'Valor',
                ...props,
            },
        })
    }

    it('renderiza o campo monetário', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.exists()).toBe(true)
        expect(input.attributes('id')).toBe('amount')
        expect(input.attributes('type')).toBe('text')
        expect(input.attributes('inputmode')).toBe('decimal')
    })

    it('exibe o valor formatado em pt-BR fora do foco', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('input').element.value).toBe('1.234,56')
    })

    it('exibe o símbolo do real por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.text()).toContain('R$')
    })

    it('permite ocultar o símbolo monetário', () => {
        const wrapper = mountComponent({
            showCurrency: false,
        })

        expect(wrapper.text()).not.toContain('R$')
    })

    it('renderiza a label associada ao controle', () => {
        const wrapper = mountComponent()

        const label = wrapper.get('label')

        expect(label.text()).toBe('Valor')
        expect(label.attributes('for')).toBe('amount')
    })

    it('remove o agrupamento ao receber foco', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')

        expect(input.element.value).toBe('1234,56')
    })

    it('emite valor numérico durante a edição', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.setValue('2500,75')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates).toBeTruthy()
        expect(updates.at(-1)).toEqual([2500.75])
    })

    it('restaura a formatação ao perder foco', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.setValue('2500,75')
        await input.trigger('blur')

        await wrapper.setProps({
            modelValue: 2500.75,
        })

        expect(input.element.value).toBe('2.500,75')
    })

    it('emite null quando o campo é apagado', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.setValue('')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([null])
    })

    it('emite zero quando allowEmpty é false', async () => {
        const wrapper = mountComponent({
            allowEmpty: false,
        })

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.setValue('')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([0])
    })

    it('aplica o limite máximo', async () => {
        const wrapper = mountComponent({
            max: 1000,
        })

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.setValue('5000')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([1000])
    })

    it('aceita valores negativos quando habilitado', async () => {
        const wrapper = mountComponent({
            modelValue: 0,
            allowNegative: true,
        })

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.setValue('-250,75')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([-250.75])
    })

    it('remove o sinal negativo por padrão', async () => {
        const wrapper = mountComponent({
            modelValue: 0,
        })

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.setValue('-250,75')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([250.75])
    })

    it('formata o valor usando locale en-US', () => {
        const wrapper = mountComponent({
            locale: 'en-US',
            currency: 'USD',
        })

        expect(wrapper.get('input').element.value).toBe('1,234.56')
    })

    it('exibe o símbolo da moeda configurada', () => {
        const wrapper = mountComponent({
            locale: 'en-US',
            currency: 'USD',
        })

        expect(wrapper.text()).toContain('$')
    })

    it('respeita a precisão configurada', () => {
        const wrapper = mountComponent({
            modelValue: 5.432,
            precision: 3,
        })

        expect(wrapper.get('input').element.value).toBe('5,432')
    })

    it('mantém zeros decimais conforme a precisão', () => {
        const wrapper = mountComponent({
            modelValue: 12,
            precision: 3,
        })

        expect(wrapper.get('input').element.value).toBe('12,000')
    })

    it('aplica o limite mínimo durante a edição', async () => {
        const wrapper = mountComponent({
            modelValue: 100,
            min: 50,
        })

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.setValue('10')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([50])
    })

    it('emite os eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })
})
