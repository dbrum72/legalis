import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppAutocomplete from '@/components/forms/fields/AppAutocomplete/index.vue'

describe('AppAutocomplete', () => {
    function mountComponent(props = {}) {
        return mount(AppAutocomplete, {
            props: {
                modelValue: null,
                searchValue: '',
                id: 'user',
                label: 'Usuário',
                placeholder: 'Digite para pesquisar...',
                options: [
                    {
                        label: 'Administrador',
                        value: 10,
                    },
                    {
                        label: 'Operador',
                        value: 20,
                    },
                    {
                        label: 'Convidado',
                        value: 30,
                    },
                ],
                ...props,
            },
        })
    }

    it('renderiza o campo como combobox', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('id')).toBe('user')
        expect(input.attributes('role')).toBe('combobox')
        expect(input.attributes('type')).toBe('text')
    })

    it('renderiza a label associada ao controle', () => {
        const wrapper = mountComponent()

        const label = wrapper.get('label')

        expect(label.text()).toBe('Usuário')
        expect(label.attributes('for')).toBe('user')
    })

    it('encaminha placeholder e autocomplete', () => {
        const wrapper = mountComponent({
            autocomplete: 'off',
        })

        const input = wrapper.get('input')

        expect(input.attributes('placeholder')).toBe('Digite para pesquisar...')

        expect(input.attributes('autocomplete')).toBe('off')
    })

    it('abre a lista ao receber foco', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')

        expect(wrapper.find('[role="listbox"]').exists()).toBe(true)

        expect(input.attributes('aria-expanded')).toBe('true')
    })

    it('renderiza todas as opções ao abrir', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('[role="option"]')

        expect(options).toHaveLength(3)
        expect(options[0].text()).toBe('Administrador')
        expect(options[1].text()).toBe('Operador')
        expect(options[2].text()).toBe('Convidado')
    })

    it('emite open ao abrir a lista', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('focus')

        expect(wrapper.emitted('open')).toHaveLength(1)
    })

    it('encaminha disabled e required', () => {
        const wrapper = mountComponent({
            disabled: true,
            required: true,
        })

        const input = wrapper.get('input')

        expect(input.attributes()).toHaveProperty('disabled')
        expect(input.attributes()).toHaveProperty('required')
    })

    it('emite focus e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('emite update:searchValue durante a digitação', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.setValue('ope')

        const updates = wrapper.emitted('update:searchValue')

        expect(updates).toBeTruthy()
        expect(updates.at(-1)).toEqual(['ope'])
    })

    it('filtra opções conforme searchValue', async () => {
        const wrapper = mountComponent({
            searchValue: 'ope',
        })

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        expect(options).toHaveLength(1)
        expect(options[0].text()).toBe('Operador')
    })

    it('exibe mensagem quando não há resultados', async () => {
        const wrapper = mountComponent({
            searchValue: 'inexistente',
        })

        await wrapper.get('input').trigger('focus')

        expect(wrapper.text()).toContain('Nenhum resultado encontrado.')
    })

    it('respeita minSearchLength', async () => {
        const wrapper = mountComponent({
            searchValue: 'a',
            minSearchLength: 2,
        })

        await wrapper.get('input').trigger('focus')

        expect(wrapper.findAll('.autocomplete-control__option')).toHaveLength(0)
    })

    it('seleciona uma opção com o mouse', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        await options[1].trigger('mousedown')

        const modelUpdates = wrapper.emitted('update:modelValue')

        const searchUpdates = wrapper.emitted('update:searchValue')

        expect(modelUpdates.at(-1)).toEqual([20])
        expect(searchUpdates.at(-1)).toEqual(['Operador'])
    })

    it('fecha a lista após selecionar uma opção', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        await options[0].trigger('mousedown')

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false)

        expect(wrapper.emitted('close')).toHaveLength(1)
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

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        await options[1].trigger('mousedown')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([false])
    })

    it('aceita opções primitivas', async () => {
        const wrapper = mountComponent({
            options: ['Aberto', 'Fechado'],
        })

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        await options[1].trigger('mousedown')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual(['Fechado'])
    })

    it('navega pelas opções com ArrowDown', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('keydown', {
            key: 'ArrowDown',
        })

        expect(input.attributes('aria-activedescendant')).toBe('user-option-1')
    })

    it('navega pelas opções com ArrowUp', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('keydown', {
            key: 'ArrowUp',
        })

        expect(input.attributes('aria-activedescendant')).toBe('user-option-2')
    })

    it('seleciona a opção ativa com Enter', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('keydown', {
            key: 'ArrowDown',
        })
        await input.trigger('keydown', {
            key: 'Enter',
        })

        const modelUpdates = wrapper.emitted('update:modelValue')

        const searchUpdates = wrapper.emitted('update:searchValue')

        expect(modelUpdates.at(-1)).toEqual([20])
        expect(searchUpdates.at(-1)).toEqual(['Operador'])
    })

    it('fecha a lista com Escape', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('keydown', {
            key: 'Escape',
        })

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false)

        expect(wrapper.emitted('close')).toHaveLength(1)
    })

    it('associa o input à listbox com aria-controls', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')

        expect(input.attributes('aria-controls')).toBe('user-listbox')

        expect(wrapper.get('[role="listbox"]').attributes('id')).toBe('user-listbox')
    })

    it('marca a opção selecionada com aria-selected', async () => {
        const wrapper = mountComponent({
            modelValue: 20,
        })

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        expect(options[0].attributes('aria-selected')).toBe('false')

        expect(options[1].attributes('aria-selected')).toBe('true')
    })

    it('associa hint por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Digite para localizar um usuário.',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('user-hint')
    })

    it('associa erro por aria-describedby', () => {
        const wrapper = mountComponent({
            error: 'Selecione um usuário.',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('user-error')
    })

    it('marca o combobox como inválido quando há erro', () => {
        const wrapper = mountComponent({
            error: 'Selecione um usuário.',
        })

        expect(wrapper.get('input').attributes('aria-invalid')).toBe('true')
    })
})
