import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import RadioGroup from '@/components/forms/groups/RadioGroup/index.vue'

describe('RadioGroup', () => {
    function mountComponent(props = {}) {
        return mount(RadioGroup, {
            props: {
                modelValue: null,
                id: 'gender',
                name: 'gender',
                label: 'Sexo',
                options: [
                    {
                        label: 'Masculino',
                        value: 'M',
                    },
                    {
                        label: 'Feminino',
                        value: 'F',
                    },
                ],
                ...props,
            },
        })
    }

    it('renderiza fieldset e legend', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('fieldset').exists()).toBe(true)
        expect(wrapper.get('legend').text()).toBe('Sexo')
    })

    it('não renderiza legend quando label está vazia', () => {
        const wrapper = mountComponent({
            label: '',
        })

        expect(wrapper.find('legend').exists()).toBe(false)
    })

    it('renderiza todas as opções', () => {
        const wrapper = mountComponent()

        const radios = wrapper.findAll('input[type="radio"]')

        expect(radios).toHaveLength(2)

        expect(wrapper.text()).toContain('Masculino')
        expect(wrapper.text()).toContain('Feminino')
    })

    it('compartilha o mesmo name entre as opções', () => {
        const wrapper = mountComponent()

        const radios = wrapper.findAll('input[type="radio"]')

        expect(radios[0].attributes('name')).toBe('gender')
        expect(radios[1].attributes('name')).toBe('gender')
    })

    it('gera ids previsíveis para as opções', () => {
        const wrapper = mountComponent()

        const radios = wrapper.findAll('input[type="radio"]')

        expect(radios[0].attributes('id')).toBe('gender-option-0')

        expect(radios[1].attributes('id')).toBe('gender-option-1')
    })

    it('reflete modelValue na opção selecionada', () => {
        const wrapper = mountComponent({
            modelValue: 'F',
        })

        const radios = wrapper.findAll('input[type="radio"]')

        expect(radios[0].element.checked).toBe(false)
        expect(radios[1].element.checked).toBe(true)
    })

    it('emite update:modelValue ao selecionar uma opção', async () => {
        const wrapper = mountComponent()

        const radios = wrapper.findAll('input[type="radio"]')

        await radios[1].trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([['F']])
    })

    it('preserva valores numéricos', async () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Baixa',
                    value: 1,
                },
                {
                    label: 'Alta',
                    value: 2,
                },
            ],
        })

        const radios = wrapper.findAll('input[type="radio"]')

        await radios[1].trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([[2]])
    })

    it('preserva valores booleanos', async () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Sim',
                    value: true,
                },
                {
                    label: 'Não',
                    value: false,
                },
            ],
        })

        const radios = wrapper.findAll('input[type="radio"]')

        await radios[1].trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([[false]])
    })

    it('aceita opções primitivas', () => {
        const wrapper = mountComponent({
            options: ['Aberto', 'Fechado'],
        })

        expect(wrapper.text()).toContain('Aberto')
        expect(wrapper.text()).toContain('Fechado')
    })

    it('aceita optionLabel e optionValue personalizados', async () => {
        const wrapper = mountComponent({
            optionLabel: 'name',
            optionValue: 'id',
            options: [
                {
                    id: 10,
                    name: 'Administrador',
                },
                {
                    id: 20,
                    name: 'Operador',
                },
            ],
        })

        expect(wrapper.text()).toContain('Administrador')

        const radios = wrapper.findAll('input[type="radio"]')

        await radios[1].trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([[20]])
    })

    it('desabilita todas as opções quando disabled', () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        const radios = wrapper.findAll('input[type="radio"]')

        expect(radios.every((radio) => radio.attributes('disabled') !== undefined)).toBe(true)

        expect(wrapper.get('fieldset').attributes()).toHaveProperty('disabled')
    })

    it('desabilita uma opção individualmente', () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Disponível',
                    value: 'A',
                },
                {
                    label: 'Indisponível',
                    value: 'B',
                    disabled: true,
                },
            ],
        })

        const radios = wrapper.findAll('input[type="radio"]')

        expect(radios[0].attributes('disabled')).toBeUndefined()

        expect(radios[1].attributes()).toHaveProperty('disabled')
    })

    it('aceita optionDisabled personalizado', () => {
        const wrapper = mountComponent({
            optionDisabled: 'blocked',
            options: [
                {
                    label: 'Livre',
                    value: 1,
                    blocked: false,
                },
                {
                    label: 'Bloqueada',
                    value: 2,
                    blocked: true,
                },
            ],
        })

        const radios = wrapper.findAll('input[type="radio"]')

        expect(radios[0].attributes('disabled')).toBeUndefined()

        expect(radios[1].attributes()).toHaveProperty('disabled')
    })

    it('renderiza indicador obrigatório apenas no legend', () => {
        const wrapper = mountComponent({
            required: true,
        })

        expect(wrapper.findAll('.radio-group__required')).toHaveLength(1)

        expect(wrapper.findAll('.app-radio__required')).toHaveLength(0)
    })

    it('marca o radiogroup como obrigatório', () => {
        const wrapper = mountComponent({
            required: true,
        })

        expect(wrapper.get('[role="radiogroup"]').attributes('aria-required')).toBe('true')
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma opção.',
        })

        const hint = wrapper.get('.radio-group__hint')

        expect(hint.text()).toBe('Selecione uma opção.')

        expect(hint.attributes('id')).toBe('gender-hint')
    })

    it('renderiza mensagem de erro', () => {
        const wrapper = mountComponent({
            error: 'Seleção obrigatória.',
        })

        const error = wrapper.get('.radio-group__error')

        expect(error.text()).toBe('Seleção obrigatória.')

        expect(error.attributes('id')).toBe('gender-error')
    })

    it('oculta hint quando existe erro', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma opção.',
            error: 'Seleção obrigatória.',
        })

        expect(wrapper.find('.radio-group__hint').exists()).toBe(false)

        expect(wrapper.text()).toContain('Seleção obrigatória.')
    })

    it('associa hint ao fieldset', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma opção.',
        })

        expect(wrapper.get('fieldset').attributes('aria-describedby')).toBe('gender-hint')
    })

    it('associa erro ao fieldset e marca como inválido', () => {
        const wrapper = mountComponent({
            error: 'Seleção obrigatória.',
        })

        const fieldset = wrapper.get('fieldset')

        expect(fieldset.attributes('aria-describedby')).toBe('gender-error')

        expect(fieldset.attributes('aria-invalid')).toBe('true')
    })

    it('aplica orientação vertical por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('fieldset').classes()).toContain('radio-group--vertical')
    })

    it('aplica orientação horizontal', () => {
        const wrapper = mountComponent({
            orientation: 'horizontal',
        })

        expect(wrapper.get('fieldset').classes()).toContain('radio-group--horizontal')
    })

    it('aplica classes de estado', () => {
        const wrapper = mountComponent({
            disabled: true,
            error: 'Seleção obrigatória.',
        })

        const classes = wrapper.get('fieldset').classes()

        expect(classes).toContain('radio-group--disabled')

        expect(classes).toContain('radio-group--invalid')
    })

    it('emite focus e blur das opções', async () => {
        const wrapper = mountComponent()

        const radio = wrapper.findAll('input[type="radio"]')[0]

        await radio.trigger('focus')
        await radio.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('não gera ids descritivos sem id', () => {
        const wrapper = mountComponent({
            id: undefined,
            hint: 'Selecione uma opção.',
        })

        expect(wrapper.get('.radio-group__hint').attributes('id')).toBeUndefined()

        expect(wrapper.get('fieldset').attributes('aria-describedby')).toBeUndefined()
    })
})
