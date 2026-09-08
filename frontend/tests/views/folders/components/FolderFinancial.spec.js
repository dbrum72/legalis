import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

const permissions = new Set()
const financialStore = {
    agreements: [], timeEntries: [], expenses: [], loading: false,
    billableMinutes: 90, billableTimeCents: 30000, reimbursableExpenseCents: 15000,
    fetchAll: vi.fn().mockResolvedValue(), createAgreement: vi.fn(), createTime: vi.fn(), createExpense: vi.fn(),
    removeAgreement: vi.fn(), removeTime: vi.fn(), removeExpense: vi.fn(),
}
vi.mock('@/stores/auth.js', () => ({ useAuthStore: () => ({ hasPermission: (name) => permissions.has(name) }) }))
vi.mock('@/stores/folder-financial.js', () => ({ useFolderFinancialStore: () => financialStore }))

import FolderFinancial from '@/views/folders/components/FolderFinancial.vue'

describe('FolderFinancial', () => {
    it('exibe visão completa para gestor financeiro', async () => {
        permissions.clear()
        ;['finance.view', 'finance.manage', 'time-entries.view', 'time-entries.create', 'expenses.view', 'expenses.create'].forEach((item) => permissions.add(item))
        const wrapper = mount(FolderFinancial, { props: { folderId: 1, clients: [{ id: 2, name: 'Cliente' }] } })

        expect(wrapper.text()).toMatch(/R\$\s300,00/)
        expect(wrapper.text()).toContain('Novo contrato')
        expect(wrapper.text()).toContain('Apontar horas')
        expect(wrapper.text()).toContain('Nova despesa')
    })

    it('não expõe contratos nem valores de honorários sem finance.view', () => {
        permissions.clear()
        permissions.add('time-entries.view')
        const wrapper = mount(FolderFinancial, { props: { folderId: 1 } })

        expect(wrapper.text()).toContain('Horas faturáveis em aberto')
        expect(wrapper.text()).not.toContain('Honorários por horas')
        expect(wrapper.text()).not.toContain('Contratos de honorários')
    })
})
