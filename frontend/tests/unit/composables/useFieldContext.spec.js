import { computed, defineComponent, h, provide, ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import { FIELD_CONTEXT } from '@/composables/field-context.js'
import { useFieldContext } from '@/composables/useFieldContext.js'

const ContextConsumer = defineComponent({
    name: 'ContextConsumer',

    setup() {
        const { fieldContext, ariaDescribedBy, ariaInvalid } = useFieldContext()

        return () =>
            h('div', {
                class: 'context-consumer',
                'data-has-context': String(Boolean(fieldContext)),
                'data-id': fieldContext?.value?.id,
                'data-describedby': ariaDescribedBy.value,
                'data-invalid': ariaInvalid.value,
            })
    },
})

function mountWithContext(context) {
    const Provider = defineComponent({
        name: 'FieldContextProvider',

        setup() {
            provide(FIELD_CONTEXT, context)

            return () => h(ContextConsumer)
        },
    })

    return mount(Provider)
}

describe('useFieldContext', () => {
    it('funciona sem provider', () => {
        const wrapper = mount(ContextConsumer)

        const consumer = wrapper.get('.context-consumer')

        expect(consumer.attributes('data-has-context')).toBe('false')

        expect(consumer.attributes('data-describedby')).toBeUndefined()

        expect(consumer.attributes('data-invalid')).toBeUndefined()
    })

    it('retorna o contexto injetado', () => {
        const context = ref({
            id: 'email',
            hintId: 'email-hint',
            errorId: 'email-error',
            invalid: false,
        })

        const wrapper = mountWithContext(context)

        const consumer = wrapper.get('.context-consumer')

        expect(consumer.attributes('data-has-context')).toBe('true')

        expect(consumer.attributes('data-id')).toBe('email')
    })

    it('utiliza o id do hint quando o campo é válido', () => {
        const context = ref({
            id: 'email',
            hintId: 'email-hint',
            errorId: 'email-error',
            invalid: false,
        })

        const wrapper = mountWithContext(context)

        const consumer = wrapper.get('.context-consumer')

        expect(consumer.attributes('data-describedby')).toBe('email-hint')

        expect(consumer.attributes('data-invalid')).toBeUndefined()
    })

    it('utiliza o id do erro quando o campo é inválido', () => {
        const context = ref({
            id: 'email',
            hintId: 'email-hint',
            errorId: 'email-error',
            invalid: true,
        })

        const wrapper = mountWithContext(context)

        const consumer = wrapper.get('.context-consumer')

        expect(consumer.attributes('data-describedby')).toBe('email-error')

        expect(consumer.attributes('data-invalid')).toBe('true')
    })

    it('retorna undefined quando não há hint', () => {
        const context = ref({
            id: 'email',
            hintId: undefined,
            errorId: 'email-error',
            invalid: false,
        })

        const wrapper = mountWithContext(context)

        expect(wrapper.get('.context-consumer').attributes('data-describedby')).toBeUndefined()
    })

    it('retorna undefined quando não há id de erro', () => {
        const context = ref({
            id: 'email',
            hintId: 'email-hint',
            errorId: undefined,
            invalid: true,
        })

        const wrapper = mountWithContext(context)

        const consumer = wrapper.get('.context-consumer')

        expect(consumer.attributes('data-describedby')).toBeUndefined()

        expect(consumer.attributes('data-invalid')).toBe('true')
    })

    it('reage à mudança do estado de validação', async () => {
        const state = ref({
            id: 'email',
            hintId: 'email-hint',
            errorId: 'email-error',
            invalid: false,
        })

        const context = computed(() => state.value)
        const wrapper = mountWithContext(context)

        const consumer = wrapper.get('.context-consumer')

        expect(consumer.attributes('data-describedby')).toBe('email-hint')

        expect(consumer.attributes('data-invalid')).toBeUndefined()

        state.value = {
            ...state.value,
            invalid: true,
        }

        await wrapper.vm.$nextTick()

        expect(consumer.attributes('data-describedby')).toBe('email-error')

        expect(consumer.attributes('data-invalid')).toBe('true')
    })

    it('reage à alteração dos ids descritivos', async () => {
        const context = ref({
            id: 'field',
            hintId: 'field-hint',
            errorId: 'field-error',
            invalid: false,
        })

        const wrapper = mountWithContext(context)
        const consumer = wrapper.get('.context-consumer')

        expect(consumer.attributes('data-describedby')).toBe('field-hint')

        context.value = {
            ...context.value,
            hintId: 'field-help',
        }

        await wrapper.vm.$nextTick()

        expect(consumer.attributes('data-describedby')).toBe('field-help')
    })
})
