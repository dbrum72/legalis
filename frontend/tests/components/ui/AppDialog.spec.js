import { beforeEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'

import AppDialog from '@/components/ui/AppDialog/index.vue'

function mountComponent(props = {}, slots = {}) {
    return mount(AppDialog, {
        attachTo: document.body,

        props: {
            open: true,
            title: 'Título do diálogo',
            ...props,
        },

        slots,
    })
}

describe('AppDialog', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    it('não renderiza quando fechado', () => {
        const wrapper = mountComponent({
            open: false,
        })

        expect(document.querySelector('.app-dialog')).toBeNull()

        wrapper.unmount()
    })

    it('renderiza quando aberto', () => {
        const wrapper = mountComponent()

        expect(document.querySelector('.app-dialog')).not.toBeNull()

        wrapper.unmount()
    })

    it('renderiza título', () => {
        const wrapper = mountComponent({
            title: 'Adicionar cliente',
        })

        expect(document.body.textContent).toContain('Adicionar cliente')

        wrapper.unmount()
    })

    it('renderiza slot default', () => {
        const wrapper = mountComponent(
            {},
            {
                default: '<div class="dialog-content">Conteúdo</div>',
            },
        )

        expect(document.querySelector('.dialog-content')).not.toBeNull()

        wrapper.unmount()
    })

    it('renderiza slot footer quando informado', () => {
        const wrapper = mountComponent(
            {},
            {
                footer: '<button class="footer-action">Salvar</button>',
            },
        )

        expect(document.querySelector('.app-dialog__footer')).not.toBeNull()

        expect(document.querySelector('.footer-action')).not.toBeNull()

        wrapper.unmount()
    })

    it('não renderiza footer sem slot', () => {
        const wrapper = mountComponent()

        expect(document.querySelector('.app-dialog__footer')).toBeNull()

        wrapper.unmount()
    })

    it('emite close pelo botão fechar', async () => {
        const wrapper = mountComponent()

        const closeButton = Array.from(document.querySelectorAll('button')).find(
            (button) => button.getAttribute('aria-label') === 'Fechar',
        )

        expect(closeButton).toBeTruthy()

        closeButton.click()

        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('close')).toHaveLength(1)

        wrapper.unmount()
    })

    it('fecha ao clicar no backdrop', async () => {
        const wrapper = mountComponent()

        const backdrop = document.querySelector('.app-dialog')

        backdrop.dispatchEvent(
            new MouseEvent('click', {
                bubbles: true,
            }),
        )

        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('close')).toHaveLength(1)

        wrapper.unmount()
    })

    it('não fecha pelo backdrop quando closeOnBackdrop é false', async () => {
        const wrapper = mountComponent({
            closeOnBackdrop: false,
        })

        const backdrop = document.querySelector('.app-dialog')

        backdrop.dispatchEvent(
            new MouseEvent('click', {
                bubbles: true,
            }),
        )

        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('close')).toBeUndefined()

        wrapper.unmount()
    })

    it('fecha ao pressionar Escape', async () => {
        const wrapper = mountComponent()

        document.dispatchEvent(
            new KeyboardEvent('keydown', {
                key: 'Escape',
                bubbles: true,
            }),
        )

        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('close')).toHaveLength(1)

        wrapper.unmount()
    })

    it('não fecha com Escape quando closeOnEscape é false', async () => {
        const wrapper = mountComponent({
            closeOnEscape: false,
        })

        document.dispatchEvent(
            new KeyboardEvent('keydown', {
                key: 'Escape',
                bubbles: true,
            }),
        )

        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('close')).toBeUndefined()

        wrapper.unmount()
    })

    it('aplica tamanho sm', () => {
        const wrapper = mountComponent({
            size: 'sm',
        })

        expect(document.querySelector('.app-dialog__panel').classList).toContain(
            'app-dialog__panel--sm',
        )

        wrapper.unmount()
    })

    it('aplica tamanho md por padrão', () => {
        const wrapper = mountComponent()

        expect(document.querySelector('.app-dialog__panel').classList).toContain(
            'app-dialog__panel--md',
        )

        wrapper.unmount()
    })

    it('aplica tamanho lg', () => {
        const wrapper = mountComponent({
            size: 'lg',
        })

        expect(document.querySelector('.app-dialog__panel').classList).toContain(
            'app-dialog__panel--lg',
        )

        wrapper.unmount()
    })

    it('prioriza elemento autofocus ao abrir', async () => {
        const wrapper = mountComponent(
            {},
            {
                default: '<input class="initial-focus" autofocus>',
            },
        )

        await wrapper.vm.$nextTick()

        const input = document.querySelector('.initial-focus')

        expect(document.activeElement).toBe(input)

        wrapper.unmount()
    })

    it('restaura foco ao elemento anterior ao fechar', async () => {
        const trigger = document.createElement('button')

        trigger.textContent = 'Abrir diálogo'

        document.body.appendChild(trigger)

        trigger.focus()

        const wrapper = mountComponent()

        await wrapper.vm.$nextTick()

        await wrapper.setProps({
            open: false,
        })

        await wrapper.vm.$nextTick()

        expect(document.activeElement).toBe(trigger)

        wrapper.unmount()
    })

    it('Tab no último elemento retorna ao primeiro', async () => {
        const wrapper = mountComponent(
            {},
            {
                default: `
                    <button class="dialog-action">
                        Ação
                    </button>
                `,
            },
        )

        await wrapper.vm.$nextTick()

        const focusable = Array.from(document.querySelectorAll('.app-dialog__panel button'))

        const first = focusable[0]

        const last = focusable[focusable.length - 1]

        last.focus()

        document.dispatchEvent(
            new KeyboardEvent('keydown', {
                key: 'Tab',
                bubbles: true,
                cancelable: true,
            }),
        )

        expect(document.activeElement).toBe(first)

        wrapper.unmount()
    })

    it('Shift Tab no primeiro elemento retorna ao último', async () => {
        const wrapper = mountComponent(
            {},
            {
                default: `
                    <button class="dialog-action">
                        Ação
                    </button>
                `,
            },
        )

        await wrapper.vm.$nextTick()

        const focusable = Array.from(document.querySelectorAll('.app-dialog__panel button'))

        const first = focusable[0]

        const last = focusable[focusable.length - 1]

        first.focus()

        document.dispatchEvent(
            new KeyboardEvent('keydown', {
                key: 'Tab',
                shiftKey: true,
                bubbles: true,
                cancelable: true,
            }),
        )

        expect(document.activeElement).toBe(last)

        wrapper.unmount()
    })
})
