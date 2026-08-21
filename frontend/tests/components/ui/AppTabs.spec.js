import { describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import AppTabs from '@/components/ui/AppTabs/index.vue'

const items = [
    {
        value: 'overview',
        label: 'Visão geral',
    },
    {
        value: 'clients',
        label: 'Partes',
    },
    {
        value: 'documents',
        label: 'Documentos',
    },
]

function mountComponent(props = {}) {
    return mount(AppTabs, {
        props: {
            modelValue: 'overview',
            items,
            ...props,
        },
    })
}

describe('AppTabs', () => {
    it('renderiza um tablist', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('[role="tablist"]').exists()).toBe(true)
    })

    it('renderiza uma tab para cada item', () => {
        const wrapper = mountComponent()

        expect(wrapper.findAll('[role="tab"]')).toHaveLength(3)
    })

    it('renderiza os labels recebidos', () => {
        const wrapper = mountComponent()

        const tabs = wrapper.findAll('[role="tab"]')

        expect(tabs[0].text()).toBe('Visão geral')
        expect(tabs[1].text()).toBe('Partes')
        expect(tabs[2].text()).toBe('Documentos')
    })

    it('marca a tab selecionada com aria-selected', () => {
        const wrapper = mountComponent({
            modelValue: 'clients',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        expect(tabs[0].attributes('aria-selected')).toBe('false')

        expect(tabs[1].attributes('aria-selected')).toBe('true')

        expect(tabs[2].attributes('aria-selected')).toBe('false')
    })

    it('mantém somente a tab selecionada no fluxo de tabulação', () => {
        const wrapper = mountComponent({
            modelValue: 'clients',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        expect(tabs[0].attributes('tabindex')).toBe('-1')

        expect(tabs[1].attributes('tabindex')).toBe('0')

        expect(tabs[2].attributes('tabindex')).toBe('-1')
    })

    it('emite update:modelValue ao selecionar uma tab', async () => {
        const wrapper = mountComponent()

        const tabs = wrapper.findAll('[role="tab"]')

        await tabs[1].trigger('click')

        expect(wrapper.emitted('update:modelValue')).toEqual([['clients']])
    })

    it('não emite alteração ao clicar na tab já selecionada', async () => {
        const wrapper = mountComponent({
            modelValue: 'overview',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        await tabs[0].trigger('click')

        expect(wrapper.emitted('update:modelValue')).toBeUndefined()
    })

    it('seleciona a próxima tab com ArrowRight', async () => {
        const wrapper = mountComponent({
            modelValue: 'overview',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        await tabs[0].trigger('keydown', {
            key: 'ArrowRight',
        })

        expect(wrapper.emitted('update:modelValue')).toEqual([['clients']])
    })

    it('seleciona a tab anterior com ArrowLeft', async () => {
        const wrapper = mountComponent({
            modelValue: 'clients',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        await tabs[1].trigger('keydown', {
            key: 'ArrowLeft',
        })

        expect(wrapper.emitted('update:modelValue')).toEqual([['overview']])
    })

    it('ArrowRight na última tab retorna para a primeira', async () => {
        const wrapper = mountComponent({
            modelValue: 'documents',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        await tabs[2].trigger('keydown', {
            key: 'ArrowRight',
        })

        expect(wrapper.emitted('update:modelValue')).toEqual([['overview']])
    })

    it('ArrowLeft na primeira tab retorna para a última', async () => {
        const wrapper = mountComponent({
            modelValue: 'overview',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        await tabs[0].trigger('keydown', {
            key: 'ArrowLeft',
        })

        expect(wrapper.emitted('update:modelValue')).toEqual([['documents']])
    })

    it('seleciona a primeira tab com Home', async () => {
        const wrapper = mountComponent({
            modelValue: 'documents',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        await tabs[2].trigger('keydown', {
            key: 'Home',
        })

        expect(wrapper.emitted('update:modelValue')).toEqual([['overview']])
    })

    it('seleciona a última tab com End', async () => {
        const wrapper = mountComponent({
            modelValue: 'overview',
        })

        const tabs = wrapper.findAll('[role="tab"]')

        await tabs[0].trigger('keydown', {
            key: 'End',
        })

        expect(wrapper.emitted('update:modelValue')).toEqual([['documents']])
    })
})
