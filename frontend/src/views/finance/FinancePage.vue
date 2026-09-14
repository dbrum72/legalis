<template>
    <PageContainer>
        <div class="finance-page">
            <header class="finance-page__header">
                <div>
                    <span class="finance-page__eyebrow">Gestão financeira</span>
                    <h1>Financeiro</h1>
                    <p>Acompanhe cobranças, vencimentos e pagamentos do escritório.</p>
                </div>
                <div class="finance-page__header-actions">
                    <AppButton
                        type="button"
                        variant="action"
                        icon="download"
                        :loading="financeStore.exportingReport"
                        @click="downloadFinancialReport"
                        >Exportar relatório</AppButton
                    ><AppButton
                        v-if="canManage && !showInvoiceForm"
                        variant="modal"
                        icon="wallet"
                        @click="showInvoiceForm = true"
                        >Nova cobrança</AppButton
                    >
                </div>
            </header>

            <div v-if="error" class="finance-page__alert" role="alert">{{ error }}</div>

            <section class="finance-page__summary" aria-label="Resumo financeiro">
                <AppCard class="finance-summary-card finance-summary-card--receivable">
                    <div class="finance-summary-card__icon" aria-hidden="true">
                        <WalletCards :size="22" :stroke-width="1.8" />
                    </div>
                    <div class="finance-summary-card__content">
                        <span class="finance-summary-card__label">A receber</span>
                        <strong class="finance-summary-card__value">{{
                            money(financeStore.summary.receivable_cents)
                        }}</strong>
                        <small class="finance-summary-card__detail">Saldo total em aberto</small>
                    </div>
                </AppCard>

                <AppCard class="finance-summary-card finance-summary-card--overdue">
                    <div class="finance-summary-card__icon" aria-hidden="true">
                        <TriangleAlert :size="22" :stroke-width="1.8" />
                    </div>
                    <div class="finance-summary-card__content">
                        <span class="finance-summary-card__label">Vencido</span>
                        <strong class="finance-summary-card__value">{{
                            money(financeStore.summary.overdue_cents)
                        }}</strong>
                        <small class="finance-summary-card__detail">
                            {{ overdueDescription }}
                        </small>
                        <AppButton
                            v-if="financeStore.summary.overdue_count"
                            type="button"
                            size="sm"
                            variant="filter"
                            class="finance-summary-card__action"
                            @click="showOverdueClients = true"
                            >Ver por cliente</AppButton
                        >
                    </div>
                </AppCard>

                <AppCard class="finance-summary-card finance-summary-card--received">
                    <div class="finance-summary-card__icon" aria-hidden="true">
                        <CircleDollarSign :size="22" :stroke-width="1.8" />
                    </div>
                    <div class="finance-summary-card__content">
                        <span class="finance-summary-card__label">Recebido no mês</span>
                        <strong class="finance-summary-card__value">{{
                            money(financeStore.summary.received_this_month_cents)
                        }}</strong>
                        <small class="finance-summary-card__detail">Pagamentos confirmados</small>
                    </div>
                </AppCard>
            </section>

            <section class="finance-aging" aria-labelledby="finance-aging-title">
                <div class="finance-aging__heading">
                    <div>
                        <span class="finance-page__eyebrow">Mapa de vencimentos</span>
                        <h2 id="finance-aging-title">Idade dos recebíveis</h2>
                    </div>
                    <p>Selecione uma faixa para filtrar as cobranças.</p>
                </div>
                <div class="finance-aging__grid">
                    <AppButton
                        v-for="bucket in agingBuckets"
                        :key="bucket.value"
                        type="button"
                        variant="ghost"
                        class="finance-aging__item"
                        :class="{
                            'finance-aging__item--active': invoiceFilters.aging === bucket.value,
                        }"
                        :aria-pressed="invoiceFilters.aging === bucket.value"
                        @click="applyAgingFilter(bucket.value)"
                    >
                        <span class="finance-aging__content">
                            <span class="finance-aging__label">{{ bucket.label }}</span>
                            <strong class="finance-aging__value">{{
                                money(bucket.balance_cents)
                            }}</strong>
                            <small class="finance-aging__count"
                                ><span>{{ bucket.count }}</span>
                                {{ bucket.count === 1 ? 'cobrança' : 'cobranças' }}</small
                            >
                        </span>
                    </AppButton>
                </div>
            </section>

            <section class="finance-forecast" aria-labelledby="finance-forecast-title">
                <div class="finance-forecast__heading">
                    <div>
                        <span class="finance-page__eyebrow">Previsibilidade de caixa</span>
                        <h2 id="finance-forecast-title">Agenda de recebimentos</h2>
                        <p>Distribuição do saldo pendente por horizonte de vencimento.</p>
                    </div>
                    <div class="finance-forecast__total">
                        <span>Próximos 90 dias</span
                        ><strong>{{ money(financeStore.summary.forecast_90_days_cents) }}</strong>
                    </div>
                </div>
                <div class="finance-forecast__grid">
                    <article
                        v-for="item in forecastBuckets"
                        :key="item.key"
                        class="finance-forecast__item"
                        :class="{ 'finance-forecast__item--overdue': item.key === 'overdue' }"
                    >
                        <div class="finance-forecast__item-heading">
                            <span>{{ item.label }}</span
                            ><small
                                >{{ item.count }}
                                {{ item.count === 1 ? 'parcela' : 'parcelas' }}</small
                            >
                        </div>
                        <strong>{{ money(item.balance_cents) }}</strong>
                        <div class="finance-forecast__track" aria-hidden="true">
                            <span :style="{ width: `${item.percentage}%` }"></span>
                        </div>
                    </article>
                </div>
                <p class="finance-forecast__note">
                    A previsão considera o saldo atual das cobranças e não representa garantia de
                    recebimento.
                </p>
            </section>

            <section class="finance-performance" aria-labelledby="finance-performance-title">
                <div class="finance-performance__heading">
                    <div>
                        <span class="finance-page__eyebrow">Desempenho financeiro</span>
                        <h2 id="finance-performance-title">Receitas, despesas e caixa</h2>
                        <p>Movimentação financeira dos últimos seis meses.</p>
                    </div>
                    <div class="finance-performance__legend">
                        <span
                            ><i
                                class="finance-performance__dot finance-performance__dot--billed"
                            ></i
                            >Faturado</span
                        ><span
                            ><i
                                class="finance-performance__dot finance-performance__dot--received"
                            ></i
                            >Recebido</span
                        >
                        <span
                            ><i
                                class="finance-performance__dot finance-performance__dot--expenses"
                            ></i
                            >Despesas pagas</span
                        >
                    </div>
                </div>
                <div
                    class="finance-performance__chart"
                    role="img"
                    aria-label="Comparativo mensal entre valores faturados, recebidos e despesas pagas"
                >
                    <article
                        v-for="month in performanceMonths"
                        :key="month.month"
                        class="finance-performance__month"
                    >
                        <div class="finance-performance__bars" aria-hidden="true">
                            <span
                                class="finance-performance__bar finance-performance__bar--billed"
                                :style="{ height: `${month.billedHeight}%` }"
                            ></span
                            ><span
                                class="finance-performance__bar finance-performance__bar--received"
                                :style="{ height: `${month.receivedHeight}%` }"
                                :title="`Recebido: ${money(month.received_cents)}`"
                            ></span
                            ><span
                                class="finance-performance__bar finance-performance__bar--expenses"
                                :style="{ height: `${month.expensesHeight}%` }"
                                :title="`Despesas: ${money(month.paid_expenses_cents)}`"
                            ></span>
                        </div>
                        <strong>{{ month.label }}</strong
                        ><small
                            :class="{
                                'finance-performance__result--negative':
                                    month.cash_result_cents < 0,
                            }"
                            >Resultado {{ money(month.cash_result_cents) }}</small
                        >
                    </article>
                </div>
                <div class="finance-performance__totals">
                    <span
                        >Faturado no período
                        <strong>{{
                            money(financeStore.summary.performance_totals?.billed_cents)
                        }}</strong></span
                    ><span
                        >Recebido no período
                        <strong>{{
                            money(financeStore.summary.performance_totals?.received_cents)
                        }}</strong></span
                    >
                    <span
                        >Despesas no período
                        <strong>{{
                            money(financeStore.summary.performance_totals?.paid_expenses_cents)
                        }}</strong></span
                    ><span
                        class="finance-performance__cash-result"
                        :class="{
                            'finance-performance__result--negative':
                                financeStore.summary.performance_totals?.cash_result_cents < 0,
                        }"
                        >Resultado de caixa
                        <strong>{{
                            money(financeStore.summary.performance_totals?.cash_result_cents)
                        }}</strong></span
                    >
                </div>
            </section>

            <AppDialog
                :open="showInvoiceForm"
                title="Nova cobrança"
                size="lg"
                @close="showInvoiceForm = false"
            >
                <form class="finance-page__form" @submit.prevent="submitInvoice">
                    <div class="finance-page__form-heading">
                        <p>Registre um recebível para o cliente.</p>
                    </div>
                    <div class="finance-page__grid">
                        <AppSelect
                            v-model="invoiceForm.client_id"
                            id="invoice-client"
                            label="Cliente"
                            :options="clientOptions"
                            placeholder="Selecione"
                            required
                        />
                        <AppSelect
                            v-model="invoiceForm.folder_id"
                            id="invoice-folder"
                            label="Pasta"
                            :options="folderOptions"
                        />
                        <div class="finance-page__wide">
                            <AppSelect
                                v-model="invoiceForm.fee_agreement_id"
                                id="invoice-agreement"
                                label="Contrato de honorários"
                                :options="agreementOptions"
                                :disabled="!invoiceForm.folder_id || loadingAgreements"
                            /><small v-if="selectedAgreement" class="finance-page__field-help">{{
                                agreementTerms(selectedAgreement)
                            }}</small>
                        </div>
                        <AppDate
                            v-model="invoiceForm.due_on"
                            id="invoice-due-on"
                            label="Vencimento"
                            required
                        />
                        <AppCurrency
                            v-model="invoiceForm.subtotal"
                            id="invoice-subtotal"
                            label="Valor"
                            :min="0.01"
                            :allow-empty="false"
                            shift-decimal
                            required
                        />
                        <AppCurrency
                            v-model="invoiceForm.discount"
                            id="invoice-discount"
                            label="Desconto"
                            :min="0"
                            :allow-empty="false"
                            shift-decimal
                        />
                        <AppInput
                            v-model.number="invoiceForm.installment_count"
                            id="invoice-installments"
                            label="Parcelas"
                            type="number"
                            min="1"
                            max="120"
                            required
                        />
                        <AppSelect
                            v-if="invoiceForm.installment_count > 1"
                            v-model="invoiceForm.installment_interval_months"
                            id="invoice-installment-interval"
                            label="Intervalo"
                            :options="installmentIntervalOptions"
                        />
                        <AppTextarea
                            v-model="invoiceForm.notes"
                            id="invoice-notes"
                            class="finance-page__wide"
                            label="Observações"
                            :rows="3"
                            :maxlength="10000"
                        />
                    </div>
                    <div class="finance-page__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="showInvoiceForm = false"
                            >Cancelar</AppButton
                        ><AppButton type="submit" variant="action" :loading="submitting"
                            >Salvar cobrança</AppButton
                        >
                    </div>
                </form>
            </AppDialog>

            <AppCard>
                <section class="finance-page__invoices">
                    <div class="finance-page__section-heading">
                        <div>
                            <h2>Contas a receber</h2>
                            <p>Cobranças atuais e histórico de recebimentos.</p>
                        </div>
                        <div class="finance-page__section-actions">
                            <span v-if="hasActiveInvoiceFilters" class="finance-page__filter-count"
                                >{{ financeStore.invoices.length }} resultado(s)</span
                            ><AppButton
                                type="button"
                                size="sm"
                                variant="action"
                                icon="download"
                                :loading="financeStore.exporting"
                                :disabled="financeStore.loading || !financeStore.invoices.length"
                                @click="downloadInvoices"
                                >Exportar CSV</AppButton
                            >
                        </div>
                    </div>
                    <form
                        class="finance-page__filters"
                        aria-label="Filtros de contas a receber"
                        @submit.prevent="applyInvoiceFilters"
                    >
                        <AppSelect
                            v-model="invoiceFilters.client_id"
                            id="invoice-filter-client"
                            label="Cliente"
                            :options="invoiceClientFilterOptions"
                        />
                        <AppSearch
                            v-model="invoiceFilters.transaction"
                            id="invoice-filter-transaction"
                            label="Transação"
                            placeholder="Cobrança ou transação"
                            clearable
                        />
                        <AppSelect
                            v-model="invoiceFilters.status"
                            id="invoice-filter-status"
                            label="Situação"
                            :options="invoiceStatusFilterOptions"
                        />
                        <AppSelect
                            v-model="invoiceFilters.period_type"
                            id="invoice-filter-period"
                            label="Vencimento"
                            :options="invoicePeriodOptions"
                        />
                        <AppSelect
                            v-model="invoiceFilters.aging"
                            id="invoice-filter-aging"
                            label="Faixa de atraso"
                            :options="invoiceAgingOptions"
                        />
                        <AppSelect
                            v-model="invoiceFilters.contact"
                            id="invoice-filter-contact"
                            label="Acompanhamento"
                            :options="invoiceContactOptions"
                        />
                        <AppSelect
                            v-model="invoiceFilters.sort"
                            id="invoice-filter-sort"
                            label="Ordenar por"
                            :options="invoiceSortOptions"
                        />
                        <AppInput
                            v-if="invoiceFilters.period_type === 'month'"
                            v-model="invoiceFilters.month"
                            id="invoice-filter-month"
                            label="Mês"
                            type="month"
                            required
                        />
                        <template v-if="invoiceFilters.period_type === 'range'">
                            <AppDate
                                v-model="invoiceFilters.due_from"
                                id="invoice-filter-from"
                                label="De"
                                required
                            />
                            <AppDate
                                v-model="invoiceFilters.due_to"
                                id="invoice-filter-to"
                                label="Até"
                                :min="invoiceFilters.due_from"
                                required
                            />
                        </template>
                        <div class="finance-page__filter-actions">
                            <AppButton
                                v-if="hasInvoiceFilterInput"
                                type="button"
                                variant="ghost"
                                :disabled="financeStore.loading"
                                @click="clearInvoiceFilters"
                                >Limpar</AppButton
                            >
                            <AppButton
                                type="submit"
                                variant="filter"
                                :loading="financeStore.loading"
                                >Filtrar</AppButton
                            >
                        </div>
                    </form>
                    <p v-if="financeStore.loading" class="finance-page__empty">
                        Carregando cobranças...
                    </p>
                    <p v-else-if="!financeStore.invoices.length" class="finance-page__empty">
                        {{
                            hasActiveInvoiceFilters
                                ? 'Nenhuma cobrança corresponde aos filtros.'
                                : 'Nenhuma cobrança registrada.'
                        }}
                    </p>
                    <AppTable
                        v-else
                        class="finance-invoice-table"
                        :columns="invoiceColumns"
                        :rows="financeStore.invoices"
                        row-key="id"
                    >
                        <template #cell-charge="{ row: invoice }">
                            <div class="finance-invoice-table__identity">
                                <strong>{{ invoice.charge_identifier ?? invoice.number }}</strong
                                ><span>{{ invoice.client?.name ?? 'Cliente não informado' }}</span
                                ><small
                                    >#{{ invoice.number }} ·
                                    {{ invoice.folder?.name ?? 'Sem pasta' }}</small
                                >
                            </div>
                        </template>
                        <template #cell-installment="{ row: invoice }"
                            ><span class="finance-invoice-table__installment"
                                >{{ invoice.installment_number ?? 1
                                }}<small>/{{ invoice.installment_count ?? 1 }}</small></span
                            ></template
                        >
                        <template #cell-due="{ row: invoice }">
                            <div class="finance-invoice-table__due">
                                <strong>{{ date(invoice.due_on) }}</strong
                                ><span
                                    class="finance-page__status"
                                    :class="`finance-page__status--${displayStatus(invoice)}`"
                                    >{{ statusLabel(invoice) }}</span
                                ><small v-if="invoice.reminders_count"
                                    >Último lembrete {{ dateTime(invoice.last_reminder_at) }}</small
                                ><small
                                    v-else-if="isOverdue(invoice)"
                                    class="finance-invoice-table__uncontacted"
                                    >Sem lembrete</small
                                >
                            </div>
                        </template>
                        <template #cell-balance="{ row: invoice }">
                            <div class="finance-invoice-table__balance">
                                <strong>{{ money(invoice.balance_cents) }}</strong
                                ><small>Total {{ money(invoice.total_cents) }}</small>
                            </div>
                        </template>
                        <template #cell-actions="{ row: invoice }">
                            <div class="finance-invoice-table__actions">
                                <div class="finance-invoice-table__primary-actions">
                                    <AppButton
                                        size="sm"
                                        variant="filter"
                                        @click="openDetails(invoice)"
                                        >Detalhes</AppButton
                                    ><AppButton
                                        v-if="
                                            canManage &&
                                            Number(invoice.balance_cents) > 0 &&
                                            invoice.status !== 'cancelled'
                                        "
                                        size="sm"
                                        variant="modal"
                                        @click="openPayment(invoice)"
                                        >Registrar pagamento</AppButton
                                    >
                                </div>
                                <div
                                    v-if="canManage"
                                    class="finance-invoice-table__secondary-actions"
                                >
                                    <AppButton
                                        v-if="
                                            ['open', 'partial'].includes(invoice.status) &&
                                            invoice.client?.email
                                        "
                                        size="sm"
                                        variant="ghost"
                                        @click="openReminder(invoice)"
                                        >Enviar lembrete</AppButton
                                    ><AppButton
                                        v-if="['draft', 'open', 'partial'].includes(invoice.status)"
                                        size="sm"
                                        variant="ghost"
                                        @click="openInvoiceEdit(invoice)"
                                        >Editar</AppButton
                                    ><AppButton
                                        v-if="
                                            isLastInstallment(invoice) &&
                                            Number(invoice.installment_count) > 1
                                        "
                                        size="sm"
                                        variant="ghost"
                                        @click="openInstallment(invoice)"
                                        >Nova parcela</AppButton
                                    ><AppButton
                                        v-if="
                                            ['draft', 'open'].includes(invoice.status) &&
                                            !invoice.payments?.length
                                        "
                                        size="sm"
                                        variant="ghost"
                                        @click="openCancellation(invoice)"
                                        >Cancelar</AppButton
                                    ><AppButton
                                        v-if="!invoice.payments?.length"
                                        class="finance-invoice-table__delete"
                                        size="sm"
                                        variant="ghost"
                                        @click="pendingDelete = invoice"
                                        >Excluir</AppButton
                                    >
                                </div>
                            </div>
                        </template>
                    </AppTable>
                    <nav
                        v-if="financeStore.pagination.total"
                        class="finance-pagination"
                        aria-label="Paginação das contas a receber"
                    >
                        <p>
                            <strong
                                >{{ financeStore.pagination.from }}–{{
                                    financeStore.pagination.to
                                }}</strong
                            >
                            de {{ financeStore.pagination.total }} cobranças
                        </p>
                        <div>
                            <AppButton
                                type="button"
                                size="sm"
                                variant="ghost"
                                :disabled="
                                    financeStore.loading || financeStore.pagination.currentPage <= 1
                                "
                                @click="
                                    financeStore.fetchPage(financeStore.pagination.currentPage - 1)
                                "
                                >Anterior</AppButton
                            ><span
                                >Página
                                <strong>{{ financeStore.pagination.currentPage }}</strong> de
                                {{ financeStore.pagination.lastPage }}</span
                            ><AppButton
                                type="button"
                                size="sm"
                                variant="ghost"
                                :disabled="
                                    financeStore.loading ||
                                    financeStore.pagination.currentPage >=
                                        financeStore.pagination.lastPage
                                "
                                @click="
                                    financeStore.fetchPage(financeStore.pagination.currentPage + 1)
                                "
                                >Próxima</AppButton
                            >
                        </div>
                    </nav>
                </section>
            </AppCard>

            <AppCard
                ><section class="finance-payables">
                    <div class="finance-page__section-heading">
                        <div>
                            <span class="finance-page__eyebrow">Saídas do escritório</span>
                            <h2>Contas a pagar</h2>
                            <p>Compromissos operacionais e pagamentos realizados.</p>
                        </div>
                        <AppButton
                            v-if="canManage"
                            type="button"
                            variant="modal"
                            @click="showPayableForm = true"
                            >Nova conta</AppButton
                        >
                    </div>
                    <form class="finance-payables__filters" @submit.prevent="applyPayableFilters">
                        <AppSearch
                            v-model="payableFilters.supplier"
                            id="payable-filter-supplier"
                            label="Fornecedor"
                            placeholder="Buscar fornecedor"
                        />
                        <AppSelect
                            v-model="payableFilters.status"
                            id="payable-filter-status"
                            label="Situação"
                            :options="payableStatusOptions"
                        />
                        <AppDate
                            v-model="payableFilters.due_from"
                            id="payable-filter-from"
                            label="De"
                        />
                        <AppDate
                            v-model="payableFilters.due_to"
                            id="payable-filter-to"
                            label="Até"
                        />
                        <div class="finance-payables__filter-actions">
                            <AppButton type="button" variant="ghost" @click="clearPayableFilters"
                                >Limpar</AppButton
                            >
                            <AppButton type="submit" variant="filter">Filtrar</AppButton>
                        </div>
                    </form>
                    <div class="finance-payables__summary">
                        <span
                            >A pagar
                            <strong>{{ money(financeStore.summary.payable_cents) }}</strong></span
                        ><span
                            >Vencido
                            <strong>{{
                                money(financeStore.summary.payable_overdue_cents)
                            }}</strong></span
                        ><span
                            >Posição líquida
                            <strong>{{
                                money(financeStore.summary.net_position_cents)
                            }}</strong></span
                        >
                    </div>
                    <div v-if="!financeStore.payables.length" class="finance-page__empty">
                        Nenhuma conta a pagar registrada.
                    </div>
                    <div v-else class="finance-payables__list">
                        <article v-for="payable in financeStore.payables" :key="payable.id">
                            <div class="finance-payables__identity">
                                <strong>{{ payable.supplier }}</strong
                                ><small
                                    >{{ payable.description }} ·
                                    {{ payable.category || 'Sem categoria' }}</small
                                >
                            </div>
                            <div class="finance-payables__meta">
                                <small>Vencimento</small><strong>{{ date(payable.due_on) }}</strong>
                            </div>
                            <div class="finance-payables__meta finance-payables__balance">
                                <small>Saldo</small
                                ><strong>{{ money(payable.balance_cents) }}</strong>
                            </div>
                            <div class="finance-payables__actions">
                                <span class="finance-payables__status">{{
                                    payableStatus(payable)
                                }}</span>
                                <AppButton
                                    type="button"
                                    size="sm"
                                    variant="modal"
                                    @click="payableDetails = payable"
                                    >Detalhes</AppButton
                                >
                                <AppButton
                                    v-if="canManage && payable.balance_cents > 0"
                                    type="button"
                                    size="sm"
                                    variant="modal"
                                    @click="openPayablePayment(payable)"
                                    >Registrar pagamento</AppButton
                                >
                            </div>
                        </article>
                    </div>
                </section></AppCard
            >

            <section class="finance-automation" aria-labelledby="finance-automation-title">
                <div class="finance-automation__heading">
                    <div>
                        <span class="finance-page__eyebrow">Régua de cobrança</span>
                        <h2 id="finance-automation-title">Lembretes automáticos</h2>
                        <p>Organize contatos antes ou depois do vencimento.</p>
                    </div>
                    <AppButton
                        v-if="canManage"
                        type="button"
                        size="sm"
                        variant="modal"
                        @click="openReminderRule()"
                        >Nova regra</AppButton
                    >
                </div>
                <div v-if="!financeStore.reminderRules.length" class="finance-automation__empty">
                    Nenhuma regra configurada. Os lembretes manuais continuam disponíveis.
                </div>
                <div v-else class="finance-automation__rules">
                    <article
                        v-for="rule in financeStore.reminderRules"
                        :key="rule.id"
                        class="finance-automation__rule"
                    >
                        <span
                            class="finance-automation__marker"
                            :class="{ 'finance-automation__marker--inactive': !rule.active }"
                            >{{ ruleTiming(rule.days_after_due) }}</span
                        >
                        <div>
                            <strong>{{ rule.name }}</strong
                            ><small>{{ rule.subject }}</small>
                        </div>
                        <span class="finance-automation__state">{{
                            rule.active ? 'Ativa' : 'Pausada'
                        }}</span>
                        <div v-if="canManage" class="finance-automation__actions">
                            <AppButton
                                type="button"
                                size="sm"
                                variant="ghost"
                                @click="openReminderRule(rule)"
                                >Editar</AppButton
                            ><AppButton
                                type="button"
                                size="sm"
                                variant="ghost"
                                @click="ruleToDelete = rule"
                                >Excluir</AppButton
                            >
                        </div>
                    </article>
                </div>
            </section>

            <AppDialog
                :open="Boolean(detailInvoiceId)"
                title="Detalhes da cobrança"
                size="lg"
                @close="closeDetails"
            >
                <div v-if="financeStore.loadingDetails" class="finance-page__empty">
                    Carregando detalhes...
                </div>
                <div v-else-if="financeStore.invoiceDetails" class="finance-details">
                    <header class="finance-details__hero">
                        <span class="finance-details__hero-icon" aria-hidden="true"
                            ><WalletCards :size="25" :stroke-width="1.7"
                        /></span>
                        <div class="finance-details__hero-copy">
                            <span>Conta a receber</span
                            ><strong>{{ financeStore.invoiceDetails.charge_identifier }}</strong
                            ><small
                                >Parcela {{ financeStore.invoiceDetails.installment_number }} de
                                {{ financeStore.invoiceDetails.installment_count }}</small
                            >
                        </div>
                        <div class="finance-details__hero-balance">
                            <span>Saldo atual</span
                            ><strong>{{ money(financeStore.invoiceDetails.balance_cents) }}</strong
                            ><span
                                class="finance-page__status"
                                :class="`finance-page__status--${displayStatus(financeStore.invoiceDetails)}`"
                                >{{ statusLabel(financeStore.invoiceDetails) }}</span
                            >
                        </div>
                    </header>
                    <section
                        v-if="financeStore.invoiceDetails.status === 'cancelled'"
                        class="finance-details__cancellation"
                    >
                        <h3>Cancelamento</h3>
                        <p>{{ financeStore.invoiceDetails.cancellation_reason }}</p>
                        <small>{{ dateTime(financeStore.invoiceDetails.cancelled_at) }}</small>
                    </section>
                    <dl class="finance-details__summary">
                        <div>
                            <dt>Cliente</dt>
                            <dd>{{ financeStore.invoiceDetails.client?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Pasta</dt>
                            <dd>{{ financeStore.invoiceDetails.folder?.name ?? 'Sem pasta' }}</dd>
                        </div>
                        <div>
                            <dt>Emissão</dt>
                            <dd>{{ date(financeStore.invoiceDetails.issued_on) }}</dd>
                        </div>
                        <div>
                            <dt>Vencimento</dt>
                            <dd>{{ date(financeStore.invoiceDetails.due_on) }}</dd>
                        </div>
                    </dl>
                    <section
                        v-if="financeStore.invoiceDetails.fee_agreement"
                        class="finance-details__panel finance-details__panel--agreement"
                    >
                        <div class="finance-details__section-heading">
                            <div>
                                <span class="finance-details__section-icon" aria-hidden="true"
                                    ><WalletCards :size="17"
                                /></span>
                                <h3>Contrato de honorários</h3>
                            </div>
                        </div>
                        <p>{{ agreementOptionLabel(financeStore.invoiceDetails.fee_agreement) }}</p>
                        <small>{{
                            agreementTerms(financeStore.invoiceDetails.fee_agreement)
                        }}</small>
                    </section>
                    <section class="finance-details__panel">
                        <div class="finance-details__section-heading">
                            <div>
                                <span class="finance-details__section-icon" aria-hidden="true"
                                    ><WalletCards :size="17"
                                /></span>
                                <h3>Composição</h3>
                            </div>
                            <small
                                >{{
                                    (financeStore.invoiceDetails.time_entries?.length || 0) +
                                    (financeStore.invoiceDetails.expenses?.length || 0)
                                }}
                                lançamento(s)</small
                            >
                        </div>
                        <div
                            v-if="
                                !financeStore.invoiceDetails.time_entries?.length &&
                                !financeStore.invoiceDetails.expenses?.length
                            "
                            class="finance-details__empty"
                        >
                            Cobrança criada sem lançamentos vinculados.
                        </div>
                        <div
                            v-for="entry in financeStore.invoiceDetails.time_entries"
                            :key="`time-${entry.id}`"
                            class="finance-details__row"
                        >
                            <div>
                                <strong>{{ entry.description }}</strong
                                ><small
                                    >{{ date(entry.worked_on) }} · {{ entry.user?.name ?? '—' }} ·
                                    {{ formatDuration(entry.duration_minutes) }}</small
                                >
                            </div>
                            <span>{{ money(timeEntryAmount(entry)) }}</span>
                        </div>
                        <div
                            v-for="expense in financeStore.invoiceDetails.expenses"
                            :key="`expense-${expense.id}`"
                            class="finance-details__row"
                        >
                            <div>
                                <strong>{{ expense.description }}</strong
                                ><small
                                    >{{ date(expense.incurred_on) }} ·
                                    {{ expense.user?.name ?? '—' }}</small
                                >
                            </div>
                            <span>{{ money(expense.amount_cents) }}</span>
                        </div>
                    </section>
                    <section class="finance-details__panel finance-details__panel--payments">
                        <div class="finance-details__section-heading">
                            <div>
                                <span class="finance-details__section-icon" aria-hidden="true"
                                    ><CircleDollarSign :size="17"
                                /></span>
                                <h3>Pagamentos</h3>
                            </div>
                            <strong
                                >{{
                                    money(financeStore.invoiceDetails.paid_cents)
                                }}
                                recebido</strong
                            >
                        </div>
                        <div
                            v-if="!financeStore.invoiceDetails.payments?.length"
                            class="finance-details__empty"
                        >
                            Nenhum pagamento registrado.
                        </div>
                        <div
                            v-for="payment in financeStore.invoiceDetails.payments"
                            :key="payment.id"
                            class="finance-details__row"
                            :class="{ 'finance-details__row--cancelled': payment.cancelled_at }"
                        >
                            <div>
                                <strong
                                    >{{ paymentMethodLabel(payment.method) }}
                                    <small
                                        v-if="payment.cancelled_at"
                                        class="finance-details__cancelled-tag"
                                        >Cancelado</small
                                    ></strong
                                ><small
                                    >{{ dateTime(payment.paid_at) }} ·
                                    {{ payment.recorded_by?.name ?? '—'
                                    }}<template v-if="payment.reference">
                                        · Ref. {{ payment.reference }}</template
                                    ></small
                                ><small v-if="payment.notes">{{ payment.notes }}</small
                                ><small v-if="payment.cancelled_at"
                                    >{{ payment.cancellation_reason }} ·
                                    {{ payment.cancelled_by?.name ?? '—' }}</small
                                >
                            </div>
                            <div class="finance-details__payment-value">
                                <span>{{ money(payment.amount_cents) }}</span>
                                <div
                                    v-if="!payment.cancelled_at"
                                    class="finance-details__payment-actions"
                                >
                                    <AppButton
                                        type="button"
                                        size="sm"
                                        variant="action"
                                        @click="openPaymentReceipt(payment)"
                                        >Comprovante</AppButton
                                    ><AppButton
                                        v-if="canManage"
                                        type="button"
                                        size="sm"
                                        variant="modal"
                                        @click="openReceiptDelivery(payment)"
                                        >Enviar</AppButton
                                    ><AppButton
                                        v-if="canManage"
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        @click="openPaymentCancellation(payment)"
                                        >Cancelar pagamento</AppButton
                                    >
                                </div>
                            </div>
                        </div>
                    </section>
                    <section class="finance-details__panel finance-details__panel--reminders">
                        <div class="finance-details__section-heading">
                            <div>
                                <span class="finance-details__section-icon" aria-hidden="true"
                                    ><Send :size="17"
                                /></span>
                                <h3>Lembretes</h3>
                            </div>
                            <AppButton
                                v-if="
                                    canManage &&
                                    ['open', 'partial'].includes(
                                        financeStore.invoiceDetails.status,
                                    ) &&
                                    financeStore.invoiceDetails.client?.email
                                "
                                type="button"
                                size="sm"
                                variant="modal"
                                @click="openReminder(financeStore.invoiceDetails)"
                                >Novo lembrete</AppButton
                            >
                        </div>
                        <div
                            v-if="!financeStore.invoiceDetails.reminders?.length"
                            class="finance-details__empty"
                        >
                            Nenhum lembrete registrado para esta cobrança.
                        </div>
                        <div
                            v-for="reminder in financeStore.invoiceDetails.reminders"
                            :key="reminder.id"
                            class="finance-details__row"
                        >
                            <div>
                                <strong>{{ reminder.subject }}</strong
                                ><small
                                    >{{
                                        reminder.status === 'scheduled'
                                            ? `Agendado para ${dateTime(reminder.scheduled_at)}`
                                            : reminder.status === 'failed'
                                              ? 'Falha no envio'
                                              : `Enviado em ${dateTime(reminder.sent_at)}`
                                    }}
                                    · {{ reminder.sent_by?.name ?? '—' }} ·
                                    {{ reminder.recipient }}</small
                                >
                            </div>
                            <span
                                class="finance-details__channel"
                                :class="`finance-details__channel--${reminder.status}`"
                                >{{ reminderStatusLabel(reminder.status) }}</span
                            >
                        </div>
                    </section>
                    <div class="finance-details__totals">
                        <span
                            >Subtotal
                            <strong>{{
                                money(financeStore.invoiceDetails.subtotal_cents)
                            }}</strong></span
                        ><span
                            >Desconto
                            <strong>{{
                                money(financeStore.invoiceDetails.discount_cents)
                            }}</strong></span
                        ><span
                            >Total
                            <strong>{{
                                money(financeStore.invoiceDetails.total_cents)
                            }}</strong></span
                        >
                    </div>
                </div>
            </AppDialog>
            <AppDialog
                :open="Boolean(payableToEdit)"
                title="Editar conta a pagar"
                size="md"
                @close="payableToEdit = null"
            >
                <form class="finance-payment" @submit.prevent="submitPayableEdit">
                    <div class="finance-page__grid">
                        <AppInput
                            v-model="payableEditForm.supplier"
                            id="payable-edit-supplier"
                            label="Fornecedor"
                            required
                        />
                        <AppInput
                            v-model="payableEditForm.category"
                            id="payable-edit-category"
                            label="Categoria"
                        />
                        <AppInput
                            v-model="payableEditForm.description"
                            id="payable-edit-description"
                            class="finance-page__wide"
                            label="Descrição"
                            required
                        />
                        <AppDate
                            v-model="payableEditForm.due_on"
                            id="payable-edit-due"
                            label="Vencimento"
                            required
                        />
                        <AppCurrency
                            v-model="payableEditForm.amount"
                            id="payable-edit-amount"
                            label="Valor"
                            :min="0.01"
                            :allow-empty="false"
                            shift-decimal
                            required
                        />
                        <AppTextarea
                            v-model="payableEditForm.notes"
                            id="payable-edit-notes"
                            class="finance-page__wide"
                            label="Observações"
                            :rows="3"
                        />
                    </div>
                    <div class="finance-page__actions">
                        <AppButton type="button" variant="ghost" @click="payableToEdit = null"
                            >Cancelar</AppButton
                        ><AppButton type="submit" variant="action" :loading="submitting"
                            >Salvar alterações</AppButton
                        >
                    </div>
                </form>
            </AppDialog>
            <AppDialog
                :open="Boolean(payableDetails)"
                title="Detalhes da conta"
                size="md"
                @close="payableDetails = null"
            >
                <div v-if="payableDetails" class="finance-payable-details">
                    <div class="finance-payment__summary">
                        <div>
                            <dt>Fornecedor</dt>
                            <dd>{{ payableDetails.supplier }}</dd>
                        </div>
                        <div>
                            <dt>Situação</dt>
                            <dd>{{ payableStatus(payableDetails) }}</dd>
                        </div>
                        <div>
                            <dt>Valor</dt>
                            <dd>{{ money(payableDetails.amount_cents) }}</dd>
                        </div>
                        <div>
                            <dt>Saldo</dt>
                            <dd>{{ money(payableDetails.balance_cents) }}</dd>
                        </div>
                    </div>
                    <div>
                        <strong>{{ payableDetails.description }}</strong>
                        <p>
                            {{ payableDetails.category || 'Sem categoria' }} · vencimento em
                            {{ date(payableDetails.due_on) }}
                        </p>
                    </div>
                    <section>
                        <h3>Histórico de pagamentos</h3>
                        <p v-if="!(payableDetails.payments || []).length">
                            Nenhum pagamento registrado.
                        </p>
                        <article
                            v-for="payment in payableDetails.payments || []"
                            :key="payment.id"
                            class="finance-payable-details__payment"
                        >
                            <div>
                                <strong>{{ money(payment.amount_cents) }}</strong
                                ><small
                                    >{{ date(payment.paid_at) }} ·
                                    {{ paymentMethodLabel(payment.method) }}</small
                                ><small v-if="payment.cancelled_at"
                                    >Estornado: {{ payment.cancellation_reason }}</small
                                >
                            </div>
                            <AppButton
                                v-if="canManage && !payment.cancelled_at"
                                type="button"
                                size="sm"
                                variant="danger"
                                @click="payablePaymentToCancel = payment"
                                >Estornar</AppButton
                            >
                        </article>
                    </section>
                    <div v-if="canManage" class="finance-payable-details__actions">
                        <div>
                            <AppButton
                                v-if="
                                    !payableDetails.paid_cents &&
                                    payableDetails.status !== 'cancelled'
                                "
                                type="button"
                                size="sm"
                                variant="action"
                                @click="openPayableDetailsAction('edit')"
                                >Editar conta</AppButton
                            >
                            <AppButton
                                v-if="payableDetails.balance_cents > 0"
                                type="button"
                                size="sm"
                                variant="modal"
                                @click="openPayableDetailsAction('payment')"
                                >Registrar pagamento</AppButton
                            >
                        </div>
                        <div>
                            <AppButton
                                v-if="
                                    !payableDetails.paid_cents &&
                                    payableDetails.status !== 'cancelled'
                                "
                                type="button"
                                size="sm"
                                variant="danger"
                                @click="openPayableDetailsAction('cancel')"
                                >Cancelar conta</AppButton
                            >
                            <AppButton
                                v-if="!(payableDetails.payments || []).length"
                                type="button"
                                size="sm"
                                variant="danger"
                                @click="openPayableDetailsAction('delete')"
                                >Excluir</AppButton
                            >
                        </div>
                    </div>
                </div>
            </AppDialog>
            <AppDialog
                :open="Boolean(payableToCancel)"
                title="Cancelar conta a pagar"
                size="sm"
                @close="payableToCancel = null"
                ><form class="finance-payment" @submit.prevent="submitPayableCancellation">
                    <AppTextarea
                        v-model="payableCancellationForm.reason"
                        id="payable-cancellation-reason"
                        label="Motivo"
                        :rows="4"
                        required
                    />
                    <div class="finance-page__actions">
                        <AppButton type="button" variant="ghost" @click="payableToCancel = null"
                            >Manter conta</AppButton
                        ><AppButton type="submit" variant="danger" :loading="submitting"
                            >Cancelar conta</AppButton
                        >
                    </div>
                </form></AppDialog
            >
            <AppDialog
                :open="Boolean(payablePaymentToCancel)"
                title="Estornar pagamento"
                size="sm"
                @close="payablePaymentToCancel = null"
                ><form class="finance-payment" @submit.prevent="submitPayablePaymentCancellation">
                    <AppTextarea
                        v-model="payablePaymentCancellationForm.reason"
                        id="payable-payment-cancellation-reason"
                        label="Motivo"
                        :rows="4"
                        required
                    />
                    <div class="finance-page__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            @click="payablePaymentToCancel = null"
                            >Manter pagamento</AppButton
                        ><AppButton type="submit" variant="danger" :loading="submitting"
                            >Confirmar estorno</AppButton
                        >
                    </div>
                </form></AppDialog
            >

            <AppDialog
                :open="showPayableForm"
                title="Nova conta a pagar"
                size="md"
                @close="showPayableForm = false"
                ><form class="finance-payment" @submit.prevent="submitPayable">
                    <div class="finance-page__grid">
                        <AppInput
                            v-model="payableForm.supplier"
                            id="payable-supplier"
                            label="Fornecedor"
                            required
                        /><AppInput
                            v-model="payableForm.category"
                            id="payable-category"
                            label="Categoria"
                        /><AppInput
                            v-model="payableForm.description"
                            id="payable-description"
                            class="finance-page__wide"
                            label="Descrição"
                            required
                        /><AppDate
                            v-model="payableForm.due_on"
                            id="payable-due"
                            label="Vencimento"
                            required
                        /><AppCurrency
                            v-model="payableForm.amount"
                            id="payable-amount"
                            label="Valor"
                            :min="0.01"
                            :allow-empty="false"
                            shift-decimal
                            required
                        /><AppTextarea
                            v-model="payableForm.notes"
                            id="payable-notes"
                            class="finance-page__wide"
                            label="Observações"
                            :rows="3"
                        />
                    </div>
                    <div class="finance-page__actions">
                        <AppButton type="button" variant="ghost" @click="showPayableForm = false"
                            >Cancelar</AppButton
                        ><AppButton type="submit" variant="action" :loading="submitting"
                            >Salvar conta</AppButton
                        >
                    </div>
                </form></AppDialog
            >
            <AppDialog
                :open="Boolean(payableToPay)"
                title="Pagar conta"
                size="sm"
                @close="payableToPay = null"
                ><form class="finance-payment" @submit.prevent="submitPayablePayment">
                    <div class="finance-payment__summary">
                        <div>
                            <dt>Fornecedor</dt>
                            <dd>{{ payableToPay?.supplier }}</dd>
                        </div>
                        <div>
                            <dt>Saldo</dt>
                            <dd>{{ money(payableToPay?.balance_cents) }}</dd>
                        </div>
                    </div>
                    <AppCurrency
                        v-model="payablePaymentForm.amount"
                        id="payable-payment-amount"
                        label="Valor pago"
                        :min="0.01"
                        :allow-empty="false"
                        shift-decimal
                        required
                    /><AppInput
                        v-model="payablePaymentForm.paid_at"
                        id="payable-payment-date"
                        label="Data e hora"
                        type="datetime-local"
                        required
                    /><AppSelect
                        v-model="payablePaymentForm.method"
                        id="payable-payment-method"
                        label="Forma"
                        :options="paymentMethodOptions"
                        required
                    /><AppInput
                        v-model="payablePaymentForm.reference"
                        id="payable-payment-reference"
                        label="Referência"
                    />
                    <div class="finance-page__actions">
                        <AppButton type="button" variant="ghost" @click="payableToPay = null"
                            >Cancelar</AppButton
                        ><AppButton type="submit" variant="action" :loading="submitting"
                            >Confirmar pagamento</AppButton
                        >
                    </div>
                </form></AppDialog
            >

            <AppDialog
                :open="Boolean(receiptPayment)"
                title="Enviar comprovante"
                size="sm"
                @close="receiptPayment = null"
            >
                <form class="finance-reminder" @submit.prevent="submitReceiptDelivery">
                    <div class="finance-reminder__intro">
                        <span class="finance-reminder__icon" aria-hidden="true"
                            ><Send :size="23"
                        /></span>
                        <div>
                            <h3>Comprovante por e-mail</h3>
                            <p>O envio ficará registrado no histórico do pagamento.</p>
                        </div>
                    </div>
                    <dl class="finance-reminder__summary">
                        <div>
                            <dt>Pagamento</dt>
                            <dd>{{ money(receiptPayment?.amount_cents) }}</dd>
                        </div>
                        <div>
                            <dt>Cobrança</dt>
                            <dd>{{ financeStore.invoiceDetails?.charge_identifier }}</dd>
                        </div>
                    </dl>
                    <AppInput
                        v-model="receiptRecipient"
                        id="receipt-recipient"
                        label="Destinatário"
                        type="email"
                        required
                    />
                    <div class="finance-reminder__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="receiptPayment = null"
                            >Cancelar</AppButton
                        ><AppButton type="submit" variant="action" :loading="submitting"
                            >Enviar comprovante</AppButton
                        >
                    </div>
                </form>
            </AppDialog>

            <AppDialog
                :open="showOverdueClients"
                title="Inadimplência por cliente"
                size="lg"
                @close="showOverdueClients = false"
            >
                <div class="finance-overdue-clients">
                    <div class="finance-overdue-clients__intro">
                        <span class="finance-overdue-clients__icon" aria-hidden="true"
                            ><TriangleAlert :size="24" :stroke-width="1.8"
                        /></span>
                        <div>
                            <h3>Carteira vencida</h3>
                            <p>Priorize os clientes com maior exposição financeira.</p>
                        </div>
                        <strong>{{ money(financeStore.summary.overdue_cents) }}</strong>
                    </div>
                    <div class="finance-overdue-clients__list">
                        <article
                            v-for="client in financeStore.summary.overdue_clients"
                            :key="client.client_id"
                            class="finance-overdue-clients__item"
                        >
                            <div class="finance-overdue-clients__identity">
                                <span>{{ clientInitials(client.client_name) }}</span>
                                <div>
                                    <strong>{{ client.client_name }}</strong
                                    ><small>{{
                                        client.client_email || 'E-mail não informado'
                                    }}</small>
                                </div>
                            </div>
                            <div class="finance-overdue-clients__metric">
                                <span>Em atraso</span
                                ><strong>{{ money(client.balance_cents) }}</strong>
                            </div>
                            <div class="finance-overdue-clients__metric">
                                <span>Parcelas</span><strong>{{ client.invoice_count }}</strong>
                            </div>
                            <div class="finance-overdue-clients__metric">
                                <span>Mais antiga</span
                                ><strong>{{ date(client.oldest_due_on) }}</strong>
                            </div>
                            <div class="finance-overdue-clients__contact">
                                <span>{{
                                    client.last_reminder_at
                                        ? `Último contato ${dateTime(client.last_reminder_at)}`
                                        : 'Sem lembrete enviado'
                                }}</span
                                ><AppButton
                                    type="button"
                                    size="sm"
                                    variant="filter"
                                    @click="filterOverdueClient(client)"
                                    >Ver cobranças</AppButton
                                >
                            </div>
                        </article>
                    </div>
                </div>
            </AppDialog>

            <AppDialog
                :open="Boolean(reminderInvoice)"
                title="Enviar lembrete"
                size="md"
                @close="reminderInvoice = null"
            >
                <form class="finance-reminder" @submit.prevent="submitReminder">
                    <div class="finance-reminder__intro">
                        <span class="finance-reminder__icon" aria-hidden="true"
                            ><Send :size="23"
                        /></span>
                        <div>
                            <h3>Lembrete de cobrança</h3>
                            <p>
                                A mensagem será enviada ao cliente e registrada no histórico da
                                cobrança.
                            </p>
                        </div>
                    </div>
                    <dl class="finance-reminder__summary">
                        <div>
                            <dt>Cobrança</dt>
                            <dd>{{ reminderInvoice?.charge_identifier }}</dd>
                        </div>
                        <div>
                            <dt>Saldo</dt>
                            <dd>{{ money(reminderInvoice?.balance_cents) }}</dd>
                        </div>
                    </dl>
                    <AppInput
                        :model-value="reminderInvoice?.client?.email"
                        id="reminder-recipient"
                        label="Destinatário"
                        readonly
                    />
                    <AppSelect
                        v-model="reminderForm.delivery"
                        id="reminder-delivery"
                        label="Envio"
                        :options="reminderDeliveryOptions"
                        required
                    />
                    <AppInput
                        v-if="reminderForm.delivery === 'scheduled'"
                        v-model="reminderForm.scheduled_at"
                        id="reminder-scheduled-at"
                        label="Data e hora do envio"
                        type="datetime-local"
                        :min="localDateTime()"
                        required
                    />
                    <AppInput
                        v-model="reminderForm.subject"
                        id="reminder-subject"
                        label="Assunto"
                        maxlength="180"
                        required
                    />
                    <AppTextarea
                        v-model="reminderForm.message"
                        id="reminder-message"
                        label="Mensagem"
                        :rows="7"
                        :maxlength="5000"
                        :hint="`${reminderForm.message.length}/5000 caracteres`"
                        required
                    />
                    <div class="finance-reminder__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="reminderInvoice = null"
                            >Cancelar</AppButton
                        ><AppButton
                            type="submit"
                            variant="action"
                            :loading="submitting"
                            :disabled="!reminderForm.subject.trim() || !reminderForm.message.trim()"
                            >{{
                                reminderForm.delivery === 'scheduled'
                                    ? 'Agendar lembrete'
                                    : 'Enviar lembrete'
                            }}</AppButton
                        >
                    </div>
                </form>
            </AppDialog>

            <AppDialog
                :open="Boolean(reminderRuleEditor)"
                :title="reminderRuleEditor?.id ? 'Editar regra' : 'Nova regra de cobrança'"
                size="md"
                @close="reminderRuleEditor = null"
            >
                <form class="finance-reminder" @submit.prevent="submitReminderRule">
                    <div class="finance-reminder__intro">
                        <span class="finance-reminder__icon" aria-hidden="true"
                            ><Send :size="23"
                        /></span>
                        <div>
                            <h3>Contato automático</h3>
                            <p>
                                O envio ocorrerá às 8h quando a cobrança alcançar o prazo definido.
                            </p>
                        </div>
                    </div>
                    <div class="finance-rule__grid">
                        <AppInput
                            v-model="reminderRuleForm.name"
                            id="rule-name"
                            label="Nome da regra"
                            maxlength="120"
                            required
                        /><AppInput
                            v-model.number="reminderRuleForm.days_after_due"
                            id="rule-days"
                            label="Dias após o vencimento"
                            type="number"
                            min="-30"
                            max="365"
                            hint="Use número negativo para enviar antes."
                            required
                        />
                    </div>
                    <AppInput
                        v-model="reminderRuleForm.subject"
                        id="rule-subject"
                        label="Assunto"
                        maxlength="180"
                        required
                    />
                    <AppTextarea
                        v-model="reminderRuleForm.message"
                        id="rule-message"
                        label="Mensagem"
                        :rows="6"
                        :maxlength="5000"
                        hint="Variáveis: {cliente}, {cobranca} e {vencimento}"
                        required
                    />
                    <label class="finance-rule__toggle"
                        ><input v-model="reminderRuleForm.active" type="checkbox" /><span
                            ><strong>Regra ativa</strong
                            ><small>Gerar novos lembretes automaticamente.</small></span
                        ></label
                    >
                    <div class="finance-reminder__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="reminderRuleEditor = null"
                            >Cancelar</AppButton
                        ><AppButton type="submit" variant="action" :loading="submitting"
                            >Salvar regra</AppButton
                        >
                    </div>
                </form>
            </AppDialog>

            <AppDialog
                :open="Boolean(invoiceToEdit)"
                title="Editar cobrança"
                size="md"
                @close="invoiceToEdit = null"
            >
                <form class="finance-edit" @submit.prevent="submitInvoiceEdit">
                    <div class="finance-edit__intro">
                        <div>
                            <span>{{ invoiceToEdit?.charge_identifier }}</span>
                            <h3>
                                Parcela {{ invoiceToEdit?.installment_number }}/{{
                                    invoiceToEdit?.installment_count
                                }}
                            </h3>
                        </div>
                        <span
                            class="finance-page__status"
                            :class="`finance-page__status--${displayStatus(invoiceToEdit || {})}`"
                            >{{ statusLabel(invoiceToEdit || {}) }}</span
                        >
                    </div>
                    <div class="finance-edit__fields">
                        <AppSelect
                            v-model="invoiceEditForm.client_id"
                            id="edit-invoice-client"
                            label="Cliente"
                            :options="clientOptions"
                            required
                        />
                        <AppSelect
                            v-model="invoiceEditForm.folder_id"
                            id="edit-invoice-folder"
                            label="Pasta"
                            :options="folderOptions"
                            placeholder="Sem pasta"
                        />
                        <AppInput
                            v-model="invoiceEditForm.due_on"
                            id="edit-invoice-due-on"
                            label="Vencimento"
                            type="date"
                            required
                        />
                        <AppCurrency
                            v-model="invoiceEditForm.subtotal"
                            id="edit-invoice-subtotal"
                            label="Valor"
                            :min="0.01"
                            :allow-empty="false"
                            shift-decimal
                            required
                        />
                        <AppCurrency
                            v-model="invoiceEditForm.discount"
                            id="edit-invoice-discount"
                            label="Desconto"
                            :min="0"
                            :allow-empty="false"
                            shift-decimal
                        />
                        <div class="finance-edit__balance">
                            <span>Valor já pago</span
                            ><strong>{{ money(invoiceToEdit?.paid_cents) }}</strong>
                        </div>
                    </div>
                    <AppTextarea
                        v-model="invoiceEditForm.notes"
                        id="edit-invoice-notes"
                        label="Observações"
                        :rows="3"
                        :maxlength="10000"
                        placeholder="Informações adicionais sobre a cobrança"
                    />
                    <div class="finance-edit__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="invoiceToEdit = null"
                            >Cancelar</AppButton
                        ><AppButton type="submit" variant="action" :loading="submitting"
                            >Salvar alterações</AppButton
                        >
                    </div>
                </form>
            </AppDialog>

            <AppDialog
                :open="Boolean(cancellationInvoice)"
                title="Cancelar cobrança"
                size="sm"
                @close="cancellationInvoice = null"
            >
                <form class="finance-cancel" @submit.prevent="submitCancellation">
                    <div class="finance-cancel__intro">
                        <span class="finance-cancel__icon" aria-hidden="true"
                            ><TriangleAlert :size="24" :stroke-width="1.8"
                        /></span>
                        <div>
                            <h3>Esta ação exige atenção</h3>
                            <p>
                                A cobrança deixará de compor o saldo a receber, mas permanecerá no
                                histórico.
                            </p>
                        </div>
                    </div>
                    <dl class="finance-cancel__summary">
                        <div>
                            <dt>Cobrança</dt>
                            <dd>{{ cancellationInvoice?.charge_identifier }}</dd>
                        </div>
                        <div>
                            <dt>Cliente</dt>
                            <dd>{{ cancellationInvoice?.client?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Parcela</dt>
                            <dd>
                                {{ cancellationInvoice?.installment_number }}/{{
                                    cancellationInvoice?.installment_count
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt>Saldo cancelado</dt>
                            <dd>{{ money(cancellationInvoice?.balance_cents) }}</dd>
                        </div>
                    </dl>
                    <div
                        v-if="
                            cancellationInvoice?.time_entries?.length ||
                            cancellationInvoice?.expenses?.length
                        "
                        class="finance-cancel__impact"
                    >
                        <strong>Lançamentos serão liberados</strong
                        ><span
                            >Horas e despesas vinculadas voltarão a ficar disponíveis para
                            faturamento.</span
                        >
                    </div>
                    <AppTextarea
                        v-model="cancellationForm.reason"
                        id="cancellation-reason"
                        class="finance-cancel__reason"
                        label="Motivo do cancelamento"
                        :hint="`${cancellationForm.reason.length}/1000 caracteres`"
                        :rows="4"
                        :maxlength="1000"
                        required
                        autofocus
                        placeholder="Descreva por que esta cobrança está sendo cancelada"
                    />
                    <div class="finance-cancel__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="cancellationInvoice = null"
                            >Manter cobrança</AppButton
                        ><AppButton
                            type="submit"
                            variant="danger"
                            :loading="submitting"
                            :disabled="!cancellationForm.reason.trim()"
                            >Confirmar cancelamento</AppButton
                        >
                    </div>
                </form>
            </AppDialog>

            <AppDialog
                :open="Boolean(installmentInvoice)"
                title="Adicionar parcela"
                size="md"
                @close="installmentInvoice = null"
            >
                <form class="finance-installment" @submit.prevent="submitInstallment">
                    <div class="finance-installment__intro">
                        <span class="finance-installment__icon" aria-hidden="true"
                            ><WalletCards :size="24" :stroke-width="1.8"
                        /></span>
                        <div>
                            <h3>Estender parcelamento</h3>
                            <p>A nova parcela será vinculada ao mesmo identificador da cobrança.</p>
                        </div>
                    </div>
                    <dl class="finance-installment__summary">
                        <div>
                            <dt>Cobrança</dt>
                            <dd>{{ installmentInvoice?.charge_identifier }}</dd>
                        </div>
                        <div>
                            <dt>Cliente</dt>
                            <dd>{{ installmentInvoice?.client?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Parcela atual</dt>
                            <dd>
                                {{ installmentInvoice?.installment_number }}/{{
                                    installmentInvoice?.installment_count
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt>Nova parcela</dt>
                            <dd>{{ Number(installmentInvoice?.installment_count || 0) + 1 }}</dd>
                        </div>
                    </dl>
                    <div class="finance-installment__fields">
                        <AppCurrency
                            v-model="installmentForm.subtotal"
                            id="installment-subtotal"
                            label="Valor"
                            :min="0.01"
                            :allow-empty="false"
                            shift-decimal
                            required
                        />
                        <AppCurrency
                            v-model="installmentForm.discount"
                            id="installment-discount"
                            label="Desconto"
                            :min="0"
                            :allow-empty="false"
                            shift-decimal
                            :error="installmentDiscountError"
                        />
                        <AppDate
                            v-model="installmentForm.due_on"
                            id="installment-due-on"
                            class="finance-installment__wide"
                            label="Vencimento"
                            required
                        />
                    </div>
                    <AppTextarea
                        v-model="installmentForm.notes"
                        id="installment-notes"
                        label="Observações"
                        :rows="3"
                        :maxlength="10000"
                        placeholder="Informações adicionais sobre esta parcela"
                    />
                    <div
                        class="finance-installment__result"
                        :class="{
                            'finance-installment__result--invalid':
                                installmentDiscountExceedsSubtotal,
                        }"
                        aria-live="polite"
                    >
                        <span>{{
                            installmentDiscountExceedsSubtotal
                                ? 'Revise os valores da parcela'
                                : 'Valor líquido da parcela'
                        }}</span
                        ><strong>{{ money(installmentNetCents) }}</strong>
                    </div>
                    <div class="finance-installment__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="installmentInvoice = null"
                            >Cancelar</AppButton
                        ><AppButton
                            type="submit"
                            variant="action"
                            :loading="submitting"
                            :disabled="
                                Number(installmentForm.subtotal) <= 0 ||
                                installmentDiscountExceedsSubtotal
                            "
                            >Adicionar parcela</AppButton
                        >
                    </div>
                </form>
            </AppDialog>

            <AppDialog
                :open="Boolean(paymentInvoice)"
                title="Registrar pagamento"
                size="md"
                @close="paymentInvoice = null"
            >
                <form class="finance-payment" @submit.prevent="submitPayment">
                    <div class="finance-payment__intro">
                        <span class="finance-payment__icon" aria-hidden="true"
                            ><CircleDollarSign :size="24"
                        /></span>
                        <div>
                            <h3>Confirmar recebimento</h3>
                            <p>Informe os dados do pagamento para atualizar o saldo da cobrança.</p>
                        </div>
                    </div>
                    <dl class="finance-payment__summary">
                        <div>
                            <dt>Cobrança</dt>
                            <dd>{{ paymentInvoice?.charge_identifier }}</dd>
                        </div>
                        <div>
                            <dt>Cliente</dt>
                            <dd>{{ paymentInvoice?.client?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Vencimento</dt>
                            <dd>{{ date(paymentInvoice?.due_on) }}</dd>
                        </div>
                        <div>
                            <dt>Saldo atual</dt>
                            <dd>{{ money(paymentInvoice?.balance_cents) }}</dd>
                        </div>
                    </dl>
                    <AppCurrency
                        v-model="paymentForm.amount"
                        id="payment-amount"
                        label="Valor"
                        :min="0.01"
                        :max="Number(paymentInvoice?.balance_cents || 0) / 100"
                        :allow-empty="false"
                        shift-decimal
                        required
                        autofocus
                    />
                    <div class="finance-payment__fields">
                        <AppInput
                            v-model="paymentForm.paid_at"
                            id="payment-date"
                            label="Data e hora"
                            type="datetime-local"
                            required
                        />
                        <AppSelect
                            v-model="paymentForm.method"
                            id="payment-method"
                            label="Forma de pagamento"
                            :options="paymentMethodOptions"
                            required
                        />
                        <AppInput
                            v-model="paymentForm.reference"
                            id="payment-reference"
                            class="finance-payment__wide"
                            label="Referência da transação"
                            hint="Opcional — identificador do banco, Pix, boleto ou operadora"
                            maxlength="120"
                            placeholder="Ex.: E2E, NSU ou código bancário"
                        />
                    </div>
                    <AppTextarea
                        v-model="paymentForm.notes"
                        id="payment-notes"
                        label="Observações"
                        :rows="3"
                        :maxlength="10000"
                        placeholder="Informações adicionais sobre o recebimento"
                    />
                    <div class="finance-payment__remaining">
                        <span>Saldo após o pagamento</span
                        ><strong>{{ money(paymentRemainingCents) }}</strong>
                    </div>
                    <div class="finance-payment__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="paymentInvoice = null"
                            >Cancelar</AppButton
                        ><AppButton
                            type="submit"
                            variant="action"
                            :loading="submitting"
                            :disabled="!paymentForm.amount"
                            >Confirmar pagamento</AppButton
                        >
                    </div>
                </form>
            </AppDialog>

            <AppDialog
                :open="Boolean(paymentToCancel)"
                title="Cancelar pagamento"
                size="sm"
                @close="paymentToCancel = null"
            >
                <form class="finance-cancel" @submit.prevent="submitPaymentCancellation">
                    <div class="finance-cancel__intro">
                        <span class="finance-cancel__icon" aria-hidden="true"
                            ><TriangleAlert :size="24"
                        /></span>
                        <div>
                            <h3>Estornar recebimento</h3>
                            <p>
                                O valor retornará ao saldo da cobrança e o pagamento permanecerá no
                                histórico.
                            </p>
                        </div>
                    </div>
                    <dl class="finance-cancel__summary">
                        <div>
                            <dt>Forma</dt>
                            <dd>{{ paymentMethodLabel(paymentToCancel?.method) }}</dd>
                        </div>
                        <div>
                            <dt>Data</dt>
                            <dd>{{ dateTime(paymentToCancel?.paid_at) }}</dd>
                        </div>
                        <div>
                            <dt>Registrado por</dt>
                            <dd>{{ paymentToCancel?.recorded_by?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Valor</dt>
                            <dd>{{ money(paymentToCancel?.amount_cents) }}</dd>
                        </div>
                    </dl>
                    <AppTextarea
                        v-model="paymentCancellationForm.reason"
                        id="payment-cancellation-reason"
                        label="Motivo do cancelamento"
                        :hint="`${paymentCancellationForm.reason.length}/1000 caracteres`"
                        :rows="4"
                        :maxlength="1000"
                        required
                        autofocus
                        placeholder="Descreva por que este pagamento está sendo cancelado"
                    />
                    <div class="finance-cancel__actions">
                        <AppButton
                            type="button"
                            variant="ghost"
                            :disabled="submitting"
                            @click="paymentToCancel = null"
                            >Manter pagamento</AppButton
                        ><AppButton
                            type="submit"
                            variant="danger"
                            :loading="submitting"
                            :disabled="!paymentCancellationForm.reason.trim()"
                            >Confirmar cancelamento</AppButton
                        >
                    </div>
                </form>
            </AppDialog>
            <AppConfirmDialog
                :open="Boolean(payableToDelete)"
                title="Excluir conta a pagar"
                message="A conta será removida permanentemente."
                confirm-label="Excluir"
                :loading="submitting"
                @cancel="payableToDelete = null"
                @confirm="confirmPayableDelete"
            />
            <AppConfirmDialog
                :open="Boolean(pendingDelete)"
                title="Excluir cobrança"
                message="Esta cobrança será removida permanentemente."
                confirm-label="Excluir"
                :loading="submitting"
                @cancel="pendingDelete = null"
                @confirm="confirmDelete"
            />
            <AppConfirmDialog
                :open="Boolean(ruleToDelete)"
                title="Excluir regra"
                message="A regra será removida. Lembretes já registrados permanecerão no histórico."
                confirm-label="Excluir"
                :loading="submitting"
                @cancel="ruleToDelete = null"
                @confirm="confirmDeleteReminderRule"
            />
        </div>
    </PageContainer>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { CircleDollarSign, Send, TriangleAlert, WalletCards } from '@lucide/vue'
import PageContainer from '@/components/layout/PageContainer/index.vue'
import {
    AppCurrency,
    AppDate,
    AppInput,
    AppSearch,
    AppSelect,
    AppTextarea,
} from '@/components/forms'
import { AppButton, AppCard, AppConfirmDialog, AppDialog, AppTable } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'
import { useFinanceStore } from '@/stores/finance.js'
import { useFoldersStore } from '@/stores/folders.js'
import { listFeeAgreements } from '@/api/folder-financial.js'

const authStore = useAuthStore()
const clientsStore = useClientsStore()
const foldersStore = useFoldersStore()
const financeStore = useFinanceStore()
const error = ref('')
const submitting = ref(false)
const showInvoiceForm = ref(false)
const paymentInvoice = ref(null)
const showPayableForm = ref(false)
const payableToPay = ref(null)
const payableToEdit = ref(null)
const payableDetails = ref(null)
const payableToCancel = ref(null)
const payableToDelete = ref(null)
const payablePaymentToCancel = ref(null)
const installmentInvoice = ref(null)
const pendingDelete = ref(null)
const detailInvoiceId = ref(null)
const receiptPayment = ref(null)
const receiptRecipient = ref('')
const showOverdueClients = ref(false)
const cancellationInvoice = ref(null)
const cancellationForm = reactive({ reason: '' })
const invoiceToEdit = ref(null)
const reminderInvoice = ref(null)
const reminderForm = reactive({ delivery: 'now', scheduled_at: '', subject: '', message: '' })
const reminderRuleEditor = ref(null)
const ruleToDelete = ref(null)
const reminderRuleForm = reactive({
    name: '',
    days_after_due: 0,
    subject: '',
    message: '',
    active: true,
})
const invoiceEditForm = reactive({
    client_id: '',
    folder_id: '',
    due_on: '',
    subtotal: 0,
    discount: 0,
    notes: '',
})
const paymentToCancel = ref(null)
const paymentCancellationForm = reactive({ reason: '' })
const availableAgreements = ref([])
const loadingAgreements = ref(false)
const localDate = () => new Date().toLocaleDateString('en-CA')
const localDateTime = () => `${localDate()}T${new Date().toTimeString().slice(0, 5)}`
const invoiceForm = reactive({
    client_id: '',
    folder_id: '',
    fee_agreement_id: '',
    due_on: localDate(),
    subtotal: 0,
    discount: 0,
    installment_count: 1,
    installment_interval_months: 1,
    notes: '',
})
const invoiceFilters = reactive({
    client_id: '',
    transaction: '',
    status: '',
    contact: '',
    period_type: 'all',
    month: localDate().slice(0, 7),
    due_from: '',
    due_to: '',
    aging: '',
    sort: 'priority',
})
const paymentForm = reactive({
    amount: null,
    paid_at: localDateTime(),
    method: 'pix',
    reference: '',
    notes: '',
})
const payableForm = reactive({
    supplier: '',
    description: '',
    category: '',
    due_on: localDate(),
    amount: 0,
    notes: '',
})
const payablePaymentForm = reactive({
    amount: 0,
    paid_at: localDateTime(),
    method: 'pix',
    reference: '',
})
const payableFilters = reactive({ supplier: '', status: '', due_from: '', due_to: '' })
const payableEditForm = reactive({
    supplier: '',
    description: '',
    category: '',
    due_on: '',
    amount: 0,
    notes: '',
})
const payableCancellationForm = reactive({ reason: '' })
const payablePaymentCancellationForm = reactive({ reason: '' })
const payableStatusOptions = [
    { value: '', label: 'Todas' },
    { value: 'open', label: 'Em aberto' },
    { value: 'partial', label: 'Parcial' },
    { value: 'paid', label: 'Paga' },
    { value: 'overdue', label: 'Vencida' },
    { value: 'cancelled', label: 'Cancelada' },
]
const paymentMethodOptions = [
    { value: 'pix', label: 'Pix' },
    { value: 'bank_transfer', label: 'Transferência bancária' },
    { value: 'cash', label: 'Dinheiro' },
    { value: 'credit_card', label: 'Cartão de crédito' },
    { value: 'debit_card', label: 'Cartão de débito' },
    { value: 'boleto', label: 'Boleto' },
    { value: 'other', label: 'Outra' },
]
const installmentIntervalOptions = [
    { value: 1, label: 'Mensal' },
    { value: 2, label: 'A cada 2 meses' },
    { value: 3, label: 'A cada 3 meses' },
    { value: 6, label: 'A cada 6 meses' },
    { value: 12, label: 'Anual' },
]
const invoiceColumns = [
    { key: 'charge', label: 'Cobrança' },
    { key: 'installment', label: 'Parcela', align: 'center' },
    { key: 'due', label: 'Vencimento' },
    { key: 'balance', label: 'Valores', align: 'end' },
    { key: 'actions', label: 'Ações', align: 'end' },
]
const installmentForm = reactive({ due_on: localDate(), subtotal: 0, discount: 0, notes: '' })
const canManage = computed(() => authStore.hasPermission('finance.manage'))
const clientOptions = computed(() =>
    clientsStore.clients.map((client) => ({ value: client.id, label: client.name })),
)
const folderOptions = computed(() => [
    { value: '', label: 'Sem pasta' },
    ...foldersStore.folders.map((folder) => ({ value: folder.id, label: folder.name })),
])
const agreementOptions = computed(() => [
    { value: '', label: agreementPlaceholder.value },
    ...availableAgreements.value.map((agreement) => ({
        value: agreement.id,
        label: agreementOptionLabel(agreement),
    })),
])
const invoiceClientFilterOptions = computed(() => [
    { value: '', label: 'Todos os clientes' },
    ...clientOptions.value,
])
const invoiceAgingOptions = computed(() => [
    { value: '', label: 'Todas as faixas' },
    ...agingBuckets.value.map(({ value, label }) => ({ value, label })),
])
const invoiceStatusFilterOptions = [
    { value: '', label: 'Todas as situações' },
    { value: 'pending', label: 'Pendentes' },
    { value: 'overdue', label: 'Vencidas' },
    { value: 'draft', label: 'Rascunhos' },
    { value: 'paid', label: 'Pagas' },
    { value: 'cancelled', label: 'Canceladas' },
]
const invoiceContactOptions = [
    { value: '', label: 'Todos os acompanhamentos' },
    { value: 'without_reminder', label: 'Vencidas sem lembrete' },
    { value: 'reminded', label: 'Vencidas com lembrete' },
]
const reminderDeliveryOptions = [
    { value: 'now', label: 'Enviar agora' },
    { value: 'scheduled', label: 'Agendar data e hora' },
]
const invoicePeriodOptions = [
    { value: 'all', label: 'Qualquer período' },
    { value: 'month', label: 'Por mês' },
    { value: 'range', label: 'Período personalizado' },
]
const invoiceSortOptions = [
    { value: 'priority', label: 'Prioridade de cobrança' },
    { value: 'due_asc', label: 'Vencimento mais próximo' },
    { value: 'due_desc', label: 'Vencimento mais distante' },
    { value: 'balance_desc', label: 'Maior saldo' },
    { value: 'recent', label: 'Mais recentes' },
]
const paymentRemainingCents = computed(() =>
    Math.max(
        0,
        Number(paymentInvoice.value?.balance_cents || 0) -
            Math.round(Number(paymentForm.amount || 0) * 100),
    ),
)
const installmentNetCents = computed(() => {
    const subtotalCents = Math.round(Number(installmentForm.subtotal || 0) * 100)
    const discountCents = Math.round(Number(installmentForm.discount || 0) * 100)
    return Math.max(0, subtotalCents - discountCents)
})
const installmentDiscountExceedsSubtotal = computed(
    () => Number(installmentForm.discount || 0) > Number(installmentForm.subtotal || 0),
)
const installmentDiscountError = computed(() =>
    installmentDiscountExceedsSubtotal.value
        ? 'O desconto não pode ser maior que o valor da parcela.'
        : '',
)
const hasInvoiceFilterInput = computed(() =>
    Boolean(
        invoiceFilters.client_id ||
        invoiceFilters.transaction ||
        invoiceFilters.status ||
        invoiceFilters.contact ||
        invoiceFilters.aging ||
        invoiceFilters.sort !== 'priority' ||
        invoiceFilters.period_type !== 'all',
    ),
)
const hasActiveInvoiceFilters = computed(() => Object.keys(financeStore.activeFilters).length > 0)
const selectedAgreement = computed(
    () =>
        availableAgreements.value.find(
            (agreement) => Number(agreement.id) === Number(invoiceForm.fee_agreement_id),
        ) ?? null,
)
const agreementPlaceholder = computed(() =>
    !invoiceForm.folder_id
        ? 'Selecione uma pasta primeiro'
        : loadingAgreements.value
          ? 'Carregando contratos...'
          : availableAgreements.value.length
            ? 'Sem contrato vinculado'
            : 'Nenhum contrato disponível',
)
const overdueDescription = computed(() => {
    const count = Number(financeStore.summary.overdue_count || 0)
    return `${count} ${count === 1 ? 'cobrança vencida' : 'cobranças vencidas'}`
})
const forecastBuckets = computed(() => {
    const labels = {
        overdue: 'Vencido',
        next_30_days: 'Até 30 dias',
        days_31_60: '31–60 dias',
        days_61_90: '61–90 dias',
        after_90_days: 'Após 90 dias',
    }
    const entries = Object.entries(labels).map(([key, label]) => ({
        key,
        label,
        count: Number(financeStore.summary.forecast?.[key]?.count || 0),
        balance_cents: Number(financeStore.summary.forecast?.[key]?.balance_cents || 0),
    }))
    const maximum = Math.max(1, ...entries.map((item) => item.balance_cents))
    return entries.map((item) => ({
        ...item,
        percentage: item.balance_cents
            ? Math.max(5, Math.round((item.balance_cents / maximum) * 100))
            : 0,
    }))
})
const performanceMonths = computed(() => {
    const values = financeStore.summary.monthly_performance ?? []
    const maximum = Math.max(
        1,
        ...values.flatMap((item) => [
            Number(item.billed_cents || 0),
            Number(item.received_cents || 0),
            Number(item.paid_expenses_cents || 0),
        ]),
    )
    return values.map((item) => ({
        ...item,
        label: new Intl.DateTimeFormat('pt-BR', { month: 'short' })
            .format(new Date(`${item.month}-01T12:00:00`))
            .replace('.', ''),
        billedHeight: Number(item.billed_cents)
            ? Math.max(4, Math.round((Number(item.billed_cents) / maximum) * 100))
            : 0,
        receivedHeight: Number(item.received_cents)
            ? Math.max(4, Math.round((Number(item.received_cents) / maximum) * 100))
            : 0,
        expensesHeight: Number(item.paid_expenses_cents)
            ? Math.max(4, Math.round((Number(item.paid_expenses_cents) / maximum) * 100))
            : 0,
        cash_result_cents: Number(item.received_cents || 0) - Number(item.paid_expenses_cents || 0),
    }))
})
const agingBuckets = computed(() =>
    [
        { value: 'current', label: 'A vencer', ...financeStore.summary.aging?.current },
        { value: 'days_1_30', label: '1–30 dias', ...financeStore.summary.aging?.days_1_30 },
        { value: 'days_31_60', label: '31–60 dias', ...financeStore.summary.aging?.days_31_60 },
        { value: 'days_61_90', label: '61–90 dias', ...financeStore.summary.aging?.days_61_90 },
        { value: 'over_90', label: 'Mais de 90 dias', ...financeStore.summary.aging?.over_90 },
    ].map((bucket) => ({
        ...bucket,
        count: Number(bucket.count || 0),
        balance_cents: Number(bucket.balance_cents || 0),
    })),
)
const money = (cents) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(
        Number(cents || 0) / 100,
    )
const date = (value) =>
    value ? new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(value)) : '—'
const dateTime = (value) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(
              new Date(value),
          )
        : '—'
const formatDuration = (minutes) =>
    `${Math.floor(Number(minutes || 0) / 60)}h ${Number(minutes || 0) % 60}min`
const clientInitials = (name) =>
    String(name || '?')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
const timeEntryAmount = (entry) =>
    Math.round((Number(entry.duration_minutes || 0) * Number(entry.hourly_rate_cents || 0)) / 60)
const paymentMethodLabel = (method) =>
    ({
        pix: 'Pix',
        bank_transfer: 'Transferência',
        cash: 'Dinheiro',
        credit_card: 'Cartão de crédito',
        debit_card: 'Cartão de débito',
        boleto: 'Boleto',
        other: 'Outra',
    })[method] ?? method
const reminderStatusLabel = (status) =>
    ({ scheduled: 'Agendado', sent: 'Enviado', failed: 'Falhou' })[status] ?? status
const isOverdue = (invoice) =>
    ['open', 'partial'].includes(invoice.status) &&
    String(invoice.due_on).slice(0, 10) < localDate()
const displayStatus = (invoice) => (isOverdue(invoice) ? 'overdue' : invoice.status)
const statusLabel = (invoice) =>
    ({
        draft: 'Rascunho',
        open: 'Em aberto',
        partial: 'Parcial',
        paid: 'Pago',
        cancelled: 'Cancelado',
        overdue: 'Vencido',
    })[displayStatus(invoice)] ?? invoice.status
const agreementTypeLabel = (type) =>
    ({ hourly: 'Por hora', fixed: 'Valor fixo', contingency: 'Êxito', hybrid: 'Híbrido' })[type] ??
    type
const agreementOptionLabel = (agreement) =>
    `${agreement.client?.name ?? 'Sem cliente'} · ${agreementTypeLabel(agreement.type)}`
function agreementTerms(agreement) {
    const terms = []
    if (agreement.fixed_fee_cents) terms.push(`Fixo de ${money(agreement.fixed_fee_cents)}`)
    if (agreement.hourly_rate_cents) terms.push(`${money(agreement.hourly_rate_cents)} por hora`)
    if (agreement.contingency_percentage)
        terms.push(`${agreement.contingency_percentage}% de êxito`)
    return terms.join(' + ')
}
function message(exception) {
    return (
        exception?.response?.data?.message ??
        Object.values(exception?.response?.data?.errors ?? {})[0]?.[0] ??
        'Não foi possível concluir a operação.'
    )
}
async function submitInvoice() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.addInvoice({
            client_id: Number(invoiceForm.client_id),
            folder_id: invoiceForm.folder_id ? Number(invoiceForm.folder_id) : null,
            fee_agreement_id: invoiceForm.fee_agreement_id
                ? Number(invoiceForm.fee_agreement_id)
                : null,
            due_on: invoiceForm.due_on,
            subtotal_cents: Math.round(invoiceForm.subtotal * 100),
            discount_cents: Math.round((invoiceForm.discount || 0) * 100),
            installment_count: invoiceForm.installment_count,
            installment_interval_months: invoiceForm.installment_interval_months,
            notes: invoiceForm.notes || null,
        })
        showInvoiceForm.value = false
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
async function openDetails(invoice) {
    detailInvoiceId.value = invoice.id
    financeStore.clearInvoiceDetails()
    try {
        await financeStore.fetchInvoice(invoice.id)
    } catch (exception) {
        error.value = message(exception)
        closeDetails()
    }
}
async function openPaymentReceipt(payment) {
    const receiptWindow = window.open('', '_blank')
    if (receiptWindow) receiptWindow.opener = null
    error.value = ''
    try {
        const blob = await financeStore.paymentReceipt(detailInvoiceId.value, payment.id)
        const objectUrl = URL.createObjectURL(blob)
        if (receiptWindow) receiptWindow.location.href = objectUrl
        else window.location.href = objectUrl
        window.setTimeout(() => URL.revokeObjectURL(objectUrl), 60000)
    } catch (exception) {
        receiptWindow?.close()
        error.value = message(exception)
    }
}
function openReceiptDelivery(payment) {
    receiptPayment.value = payment
    receiptRecipient.value = financeStore.invoiceDetails?.client?.email ?? ''
}
async function submitReceiptDelivery() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.deliverPaymentReceipt(detailInvoiceId.value, receiptPayment.value.id, {
            recipient: receiptRecipient.value,
        })
        receiptPayment.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
function openReminder(invoice) {
    reminderInvoice.value = invoice
    reminderForm.delivery = 'now'
    reminderForm.scheduled_at = ''
    reminderForm.subject = `Lembrete de cobrança ${invoice.charge_identifier}`
    reminderForm.message = `Olá, ${invoice.client?.name ?? ''}.\n\nLembramos que a parcela ${invoice.installment_number ?? 1}/${invoice.installment_count ?? 1}, com vencimento em ${date(invoice.due_on)}, possui saldo pendente de ${money(invoice.balance_cents)}.\n\nCaso o pagamento já tenha sido realizado, por favor desconsidere esta mensagem.`
}
async function submitReminder() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.sendReminder(reminderInvoice.value.id, {
            subject: reminderForm.subject,
            message: reminderForm.message,
            scheduled_at: reminderForm.delivery === 'scheduled' ? reminderForm.scheduled_at : null,
        })
        reminderInvoice.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
function ruleTiming(days) {
    const value = Number(days)
    if (value === 0) return 'No vencimento'
    return value < 0 ? `${Math.abs(value)} dia(s) antes` : `${value} dia(s) depois`
}
function openReminderRule(rule = null) {
    reminderRuleEditor.value = rule ?? {}
    Object.assign(
        reminderRuleForm,
        rule
            ? {
                  name: rule.name,
                  days_after_due: rule.days_after_due,
                  subject: rule.subject,
                  message: rule.message,
                  active: rule.active,
              }
            : {
                  name: '',
                  days_after_due: 0,
                  subject: 'Lembrete da cobrança {cobranca}',
                  message:
                      'Olá, {cliente}.\n\nA cobrança {cobranca}, com vencimento em {vencimento}, possui saldo pendente.\n\nCaso o pagamento já tenha sido realizado, desconsidere esta mensagem.',
                  active: true,
              },
    )
}
async function submitReminderRule() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.saveReminderRule({ ...reminderRuleForm }, reminderRuleEditor.value?.id)
        reminderRuleEditor.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
async function confirmDeleteReminderRule() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.removeReminderRule(ruleToDelete.value.id)
        ruleToDelete.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
function closeDetails() {
    detailInvoiceId.value = null
    financeStore.clearInvoiceDetails()
}
async function openCancellation(invoice) {
    cancellationForm.reason = ''
    cancellationInvoice.value = invoice
    try {
        cancellationInvoice.value = await financeStore.fetchInvoice(invoice.id)
    } catch (exception) {
        error.value = message(exception)
        cancellationInvoice.value = null
    }
}
async function submitCancellation() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.cancelInvoice(cancellationInvoice.value.id, {
            reason: cancellationForm.reason,
        })
        cancellationInvoice.value = null
        if (detailInvoiceId.value) closeDetails()
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
function openInvoiceEdit(invoice) {
    invoiceToEdit.value = invoice
    Object.assign(invoiceEditForm, {
        client_id: invoice.client_id,
        folder_id: invoice.folder_id ?? '',
        due_on: String(invoice.due_on || '').slice(0, 10),
        subtotal: Number(invoice.subtotal_cents) / 100,
        discount: Number(invoice.discount_cents) / 100,
        notes: invoice.notes ?? '',
    })
}
async function submitInvoiceEdit() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.updateInvoice(invoiceToEdit.value.id, {
            client_id: Number(invoiceEditForm.client_id),
            folder_id: invoiceEditForm.folder_id ? Number(invoiceEditForm.folder_id) : null,
            due_on: invoiceEditForm.due_on,
            subtotal_cents: Math.round(Number(invoiceEditForm.subtotal) * 100),
            discount_cents: Math.round(Number(invoiceEditForm.discount || 0) * 100),
            notes: invoiceEditForm.notes || null,
        })
        invoiceToEdit.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
function invoiceFilterPayload() {
    return {
        ...(invoiceFilters.client_id ? { client_id: Number(invoiceFilters.client_id) } : {}),
        ...(invoiceFilters.transaction ? { transaction: invoiceFilters.transaction } : {}),
        ...(invoiceFilters.status ? { status: invoiceFilters.status } : {}),
        ...(invoiceFilters.contact ? { contact: invoiceFilters.contact } : {}),
        ...(invoiceFilters.aging ? { aging: invoiceFilters.aging } : {}),
        ...(invoiceFilters.sort !== 'priority' ? { sort: invoiceFilters.sort } : {}),
        ...(invoiceFilters.period_type === 'month' ? { month: invoiceFilters.month } : {}),
        ...(invoiceFilters.period_type === 'range'
            ? { due_from: invoiceFilters.due_from, due_to: invoiceFilters.due_to }
            : {}),
    }
}
async function applyInvoiceFilters() {
    error.value = ''
    try {
        await financeStore.fetchAll(invoiceFilterPayload())
    } catch (exception) {
        error.value = message(exception)
    }
}
async function applyAgingFilter(aging) {
    invoiceFilters.aging = invoiceFilters.aging === aging ? '' : aging
    await applyInvoiceFilters()
}
async function filterOverdueClient(client) {
    showOverdueClients.value = false
    Object.assign(invoiceFilters, {
        client_id: client.client_id,
        status: 'overdue',
        aging: '',
        period_type: 'all',
    })
    await applyInvoiceFilters()
}
async function clearInvoiceFilters() {
    Object.assign(invoiceFilters, {
        client_id: '',
        transaction: '',
        status: '',
        contact: '',
        period_type: 'all',
        month: localDate().slice(0, 7),
        due_from: '',
        due_to: '',
        aging: '',
        sort: 'priority',
    })
    await applyInvoiceFilters()
}
async function downloadInvoices() {
    error.value = ''
    try {
        const blob = await financeStore.exportInvoices()
        const objectUrl = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = objectUrl
        link.download = `contas-a-receber-${localDate()}.csv`
        link.style.display = 'none'
        document.body.appendChild(link)
        link.click()
        link.remove()
        URL.revokeObjectURL(objectUrl)
    } catch (exception) {
        error.value = message(exception)
    }
}
async function downloadFinancialReport() {
    error.value = ''
    try {
        const blob = await financeStore.exportFinancialReport()
        const objectUrl = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = objectUrl
        link.download = `relatorio-financeiro-${localDate()}.csv`
        link.style.display = 'none'
        document.body.appendChild(link)
        link.click()
        link.remove()
        URL.revokeObjectURL(objectUrl)
    } catch (exception) {
        error.value = message(exception)
    }
}
const isLastInstallment = (invoice) =>
    Number(invoice.installment_number ?? 1) === Number(invoice.installment_count ?? 1)
function openInstallment(invoice) {
    installmentInvoice.value = invoice
    installmentForm.due_on = nextMonth(invoice.due_on)
    installmentForm.subtotal = 0
    installmentForm.discount = 0
    installmentForm.notes = ''
}
function nextMonth(value) {
    const source = new Date(`${String(value).slice(0, 10)}T12:00:00Z`)
    const day = source.getUTCDate()
    source.setUTCDate(1)
    source.setUTCMonth(source.getUTCMonth() + 1)
    const lastDay = new Date(
        Date.UTC(source.getUTCFullYear(), source.getUTCMonth() + 1, 0),
    ).getUTCDate()
    source.setUTCDate(Math.min(day, lastDay))
    return source.toISOString().slice(0, 10)
}
async function submitInstallment() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.addInstallment(installmentInvoice.value.id, {
            due_on: installmentForm.due_on,
            subtotal_cents: Math.round(installmentForm.subtotal * 100),
            discount_cents: Math.round((installmentForm.discount || 0) * 100),
            notes: installmentForm.notes || null,
        })
        installmentInvoice.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
function openPayment(invoice) {
    paymentInvoice.value = invoice
    Object.assign(paymentForm, {
        amount: Number(invoice.balance_cents) / 100,
        paid_at: localDateTime(),
        method: 'pix',
        reference: '',
        notes: '',
    })
}
async function submitPayment() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.addPayment(paymentInvoice.value.id, {
            paid_at: paymentForm.paid_at,
            amount_cents: Math.round(paymentForm.amount * 100),
            method: paymentForm.method,
            reference: paymentForm.reference || null,
            notes: paymentForm.notes || null,
        })
        paymentInvoice.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
async function submitPayable() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.addPayable({
            supplier: payableForm.supplier,
            description: payableForm.description,
            category: payableForm.category || null,
            due_on: payableForm.due_on,
            amount_cents: Math.round(payableForm.amount * 100),
            notes: payableForm.notes || null,
        })
        showPayableForm.value = false
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
function openPayablePayment(payable) {
    payableToPay.value = payable
    Object.assign(payablePaymentForm, {
        amount: Number(payable.balance_cents) / 100,
        paid_at: localDateTime(),
        method: 'pix',
        reference: '',
    })
}
async function submitPayablePayment() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.payPayable(payableToPay.value.id, {
            amount_cents: Math.round(payablePaymentForm.amount * 100),
            paid_at: payablePaymentForm.paid_at,
            method: payablePaymentForm.method,
            reference: payablePaymentForm.reference || null,
        })
        payableToPay.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
const payableStatus = (payable) => {
    if (payable.status === 'cancelled') return 'Cancelada'
    if (payable.status === 'paid') return 'Paga'
    if (payable.due_on < localDate() && Number(payable.balance_cents) > 0) return 'Vencida'
    if (payable.status === 'partial') return 'Parcial'
    return 'Em aberto'
}
async function applyPayableFilters() {
    error.value = ''
    try {
        await financeStore.fetchPayables({ ...payableFilters })
    } catch (exception) {
        error.value = message(exception)
    }
}
async function clearPayableFilters() {
    Object.assign(payableFilters, { supplier: '', status: '', due_from: '', due_to: '' })
    await applyPayableFilters()
}
function openPayableEdit(payable) {
    payableToEdit.value = payable
    Object.assign(payableEditForm, {
        supplier: payable.supplier,
        description: payable.description,
        category: payable.category || '',
        due_on: payable.due_on,
        amount: Number(payable.amount_cents) / 100,
        notes: payable.notes || '',
    })
}
function openPayableDetailsAction(action) {
    const payable = payableDetails.value
    if (!payable) return
    payableDetails.value = null
    if (action === 'edit') openPayableEdit(payable)
    if (action === 'payment') openPayablePayment(payable)
    if (action === 'cancel') payableToCancel.value = payable
    if (action === 'delete') payableToDelete.value = payable
}
async function submitPayableEdit() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.editPayable(payableToEdit.value.id, {
            supplier: payableEditForm.supplier,
            description: payableEditForm.description,
            category: payableEditForm.category || null,
            due_on: payableEditForm.due_on,
            amount_cents: Math.round(payableEditForm.amount * 100),
            notes: payableEditForm.notes || null,
        })
        payableToEdit.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
async function submitPayableCancellation() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.voidPayable(payableToCancel.value.id, {
            reason: payableCancellationForm.reason,
        })
        payableToCancel.value = null
        payableCancellationForm.reason = ''
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
async function confirmPayableDelete() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.removePayable(payableToDelete.value.id)
        payableToDelete.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
async function submitPayablePaymentCancellation() {
    submitting.value = true
    error.value = ''
    try {
        const payableId = payableDetails.value.id
        await financeStore.voidPayablePayment(payableId, payablePaymentToCancel.value.id, {
            reason: payablePaymentCancellationForm.reason,
        })
        payableDetails.value = financeStore.payables.find((item) => item.id === payableId) || null
        payablePaymentToCancel.value = null
        payablePaymentCancellationForm.reason = ''
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
function openPaymentCancellation(payment) {
    paymentCancellationForm.reason = ''
    paymentToCancel.value = payment
}
async function submitPaymentCancellation() {
    submitting.value = true
    error.value = ''
    try {
        await financeStore.cancelPayment(detailInvoiceId.value, paymentToCancel.value.id, {
            reason: paymentCancellationForm.reason,
        })
        paymentToCancel.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
async function confirmDelete() {
    submitting.value = true
    try {
        await financeStore.removeInvoice(pendingDelete.value.id)
        pendingDelete.value = null
    } catch (exception) {
        error.value = message(exception)
    } finally {
        submitting.value = false
    }
}
watch(
    () => invoiceForm.folder_id,
    async (folderId) => {
        invoiceForm.fee_agreement_id = ''
        availableAgreements.value = []
        if (!folderId) return
        loadingAgreements.value = true
        const requestedFolder = Number(folderId)
        try {
            const { data } = await listFeeAgreements(requestedFolder)
            if (Number(invoiceForm.folder_id) === requestedFolder)
                availableAgreements.value = data.filter((agreement) =>
                    ['draft', 'active'].includes(agreement.status),
                )
        } catch (exception) {
            error.value = message(exception)
        } finally {
            if (Number(invoiceForm.folder_id) === requestedFolder) loadingAgreements.value = false
        }
    },
)
watch(
    () => invoiceForm.fee_agreement_id,
    () => {
        const agreement = selectedAgreement.value
        if (!agreement) return
        invoiceForm.client_id = agreement.client_id
        if (Number(agreement.fixed_fee_cents) > 0)
            invoiceForm.subtotal = Number(agreement.fixed_fee_cents) / 100
    },
)
onMounted(async () => {
    try {
        await Promise.all([
            financeStore.fetchAll(),
            financeStore.fetchReminderRules(),
            financeStore.fetchPayables(),
            clientsStore.fetchClients(),
            foldersStore.fetchFolders(),
        ])
    } catch (exception) {
        error.value = message(exception)
    }
})
</script>

<style scoped>
.finance-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}
.finance-page__header,
.finance-page__section-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--space-4);
}
.finance-page h1,
.finance-page h2,
.finance-page p {
    margin: 0;
}
.finance-page__header p,
.finance-page__section-heading p {
    margin-top: var(--space-2);
    color: var(--color-text-muted);
}
.finance-page__eyebrow {
    color: var(--color-brand-secondary);
    font-size: var(--font-size-sm);
    font-weight: 700;
    text-transform: uppercase;
}
.finance-page__summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--space-4);
}
.finance-page__summary :deep(.app-card) {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}
.finance-page__summary span,
.finance-page__summary small {
    color: var(--color-text-muted);
}
.finance-page__summary strong {
    color: var(--color-brand);
    font-size: 1.5rem;
}
.finance-page__danger {
    color: var(--color-danger) !important;
}
.finance-page__form,
.finance-page__invoices,
.finance-page__payment {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-page__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}
.finance-page label {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
    font-weight: 600;
}
.finance-page input,
.finance-page select,
.finance-page textarea {
    padding: 0.65rem 0.75rem;
    color: var(--color-text);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font: inherit;
}
.finance-page__wide {
    grid-column: 1/-1;
}
.finance-page__actions,
.finance-page__row-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
}
.finance-page__table-wrap {
    overflow-x: auto;
}
.finance-page table {
    width: 100%;
    border-collapse: collapse;
}
.finance-page th,
.finance-page td {
    padding: var(--space-3);
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}
.finance-page th {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.finance-page td > strong,
.finance-page td > small {
    display: block;
}
.finance-page td small {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
}
.finance-page__status {
    display: inline-flex;
    padding: 0.25rem 0.55rem;
    background: var(--color-surface-muted);
    border-radius: 999px;
    font-size: var(--font-size-xs);
    font-weight: 700;
}
.finance-page__status--paid {
    color: var(--color-brand-secondary);
    background: var(--color-surface-secondary-soft);
}
.finance-page__status--overdue {
    color: var(--color-danger);
    background: var(--color-danger-soft);
}
.finance-page__alert {
    padding: var(--space-3);
    color: var(--color-danger);
    background: var(--color-danger-soft);
    border-radius: var(--radius-md);
}
.finance-page__empty {
    padding: var(--space-6);
    color: var(--color-text-muted);
    text-align: center;
}
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(50%);
}
@media (max-width: 760px) {
    .finance-page__summary,
    .finance-page__grid {
        grid-template-columns: 1fr;
    }
    .finance-page__wide {
        grid-column: auto;
    }
    .finance-page__header {
        flex-direction: column;
    }
}

.finance-page__header {
    padding-bottom: var(--space-2);
}
.finance-page__header-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.finance-automation {
    display: flex;
    padding: var(--space-5);
    flex-direction: column;
    gap: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: 0 0.3rem 1rem rgb(53 37 27 / 0.05);
}
.finance-payables {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-payables__summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-payables__summary span {
    display: flex;
    padding: var(--space-3) var(--space-4);
    flex-direction: column;
    gap: var(--space-1);
    color: var(--color-text-muted);
    border-right: 1px solid var(--color-border);
    font-size: var(--font-size-xs);
}
.finance-payables__summary span:last-child {
    border-right: 0;
    background: var(--color-surface-highlight-soft);
}
.finance-payables__summary strong {
    color: var(--color-brand);
    font-size: var(--font-size-lg);
}
.finance-payables__filters {
    display: grid;
    grid-template-columns: minmax(12rem, 2fr) minmax(9rem, 1fr) repeat(2, minmax(10rem, 1fr));
    align-items: end;
    column-gap: var(--space-4);
    row-gap: var(--space-3);
    padding: var(--space-4);
    background: var(--color-surface-highlight-soft);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-payables__filters > * {
    min-width: 0;
}
.finance-payables__filters :deep(.app-field),
.finance-payables__filters :deep(.app-field__control),
.finance-payables__filters :deep(.app-field__input) {
    min-width: 0;
    max-width: 100%;
}
.finance-payables__filter-actions {
    display: flex;
    grid-column: 1/-1;
    padding-top: var(--space-1);
    justify-content: flex-end;
    gap: var(--space-2);
}
.finance-payables__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}
.finance-payables__list {
    display: grid;
    gap: var(--space-3);
}
.finance-payables__list article {
    display: grid;
    grid-template-columns: minmax(14rem, 1fr) minmax(8rem, auto) minmax(8rem, auto);
    padding: var(--space-4);
    align-items: center;
    gap: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    box-shadow: 0 2px 7px rgb(53 37 27 / 0.04);
}
.finance-payables__status {
    padding: var(--space-1) var(--space-2);
    color: var(--color-brand);
    background: var(--color-surface-highlight-soft);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: 700;
}
.finance-payable-details {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-payable-details p,
.finance-payable-details small {
    color: var(--color-text-muted);
}
.finance-payable-details section {
    padding-top: var(--space-4);
    border-top: 1px solid var(--color-border);
}
.finance-payable-details__payment {
    display: flex;
    padding: var(--space-3) 0;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
    border-bottom: 1px solid var(--color-border);
}
.finance-payable-details__payment > div {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-payables__identity,
.finance-payables__meta {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-payables__identity > strong {
    color: var(--color-brand);
    font-size: var(--font-size-base);
}
.finance-payables__meta > strong {
    color: var(--color-text);
    font-size: var(--font-size-sm);
}
.finance-payables__balance > strong {
    color: var(--color-brand-secondary-active);
    font-size: var(--font-size-base);
}
.finance-payables__list article > .finance-payables__actions {
    display: flex;
    grid-column: 1/-1;
    padding-top: var(--space-3);
    flex-direction: row;
    justify-content: flex-end;
    align-items: center;
    gap: var(--space-2);
    border-top: 1px solid var(--color-border);
}
.finance-payables__actions .finance-payables__status {
    margin-right: auto;
}
.finance-payable-details__actions {
    display: flex;
    padding-top: var(--space-4);
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
    border-top: 1px solid var(--color-border);
}
.finance-payable-details__actions > div {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}
.finance-payables__list small {
    color: var(--color-text-muted);
}
.finance-forecast {
    display: flex;
    padding: var(--space-5);
    flex-direction: column;
    gap: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: 0 0.3rem 1rem rgb(53 37 27 / 0.05);
}
.finance-forecast__heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: var(--space-4);
}
.finance-forecast__heading h2 {
    margin-top: var(--space-1);
    color: var(--color-brand);
    font-size: var(--font-size-lg);
}
.finance-forecast__heading p {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.finance-forecast__total {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--space-1);
}
.finance-forecast__total span {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-forecast__total strong {
    color: var(--color-brand-secondary-active);
    font-size: var(--font-size-xl);
}
.finance-forecast__grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-forecast__item {
    display: flex;
    min-width: 0;
    padding: var(--space-4);
    flex-direction: column;
    gap: var(--space-3);
    border-right: 1px solid var(--color-border);
}
.finance-forecast__item:last-child {
    border-right: 0;
}
.finance-forecast__item-heading {
    display: flex;
    min-height: 2.4rem;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-forecast__item-heading span {
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
    font-weight: 700;
}
.finance-forecast__item-heading small,
.finance-forecast__note {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-forecast__item > strong {
    overflow: hidden;
    color: var(--color-brand);
    font-size: var(--font-size-lg);
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-forecast__track {
    height: 0.35rem;
    overflow: hidden;
    background: var(--color-surface-muted);
    border-radius: 999px;
}
.finance-forecast__track span {
    display: block;
    height: 100%;
    background: var(--color-brand-secondary);
    border-radius: inherit;
}
.finance-forecast__item--overdue {
    background: color-mix(in srgb, var(--color-danger-soft) 55%, var(--color-surface));
}
.finance-forecast__item--overdue > strong {
    color: var(--color-danger);
}
.finance-forecast__item--overdue .finance-forecast__track span {
    background: var(--color-danger);
}
.finance-forecast__note {
    margin: 0;
}
.finance-performance {
    display: flex;
    padding: var(--space-5);
    flex-direction: column;
    gap: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: 0 0.3rem 1rem rgb(53 37 27 / 0.05);
}
.finance-performance__heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: var(--space-4);
}
.finance-performance__heading h2 {
    margin-top: var(--space-1);
    color: var(--color-brand);
    font-size: var(--font-size-lg);
}
.finance-performance__heading p {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.finance-performance__legend {
    display: flex;
    gap: var(--space-4);
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-performance__legend span {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}
.finance-performance__dot {
    width: 0.65rem;
    height: 0.65rem;
    border-radius: 999px;
}
.finance-performance__dot--billed,
.finance-performance__bar--billed {
    background: var(--color-highlight);
}
.finance-performance__dot--received,
.finance-performance__bar--received {
    background: var(--color-brand-secondary);
}
.finance-performance__dot--expenses,
.finance-performance__bar--expenses {
    background: var(--color-danger);
}
.finance-performance__chart {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    min-height: 13rem;
    padding: var(--space-4) var(--space-3) 0;
    gap: var(--space-3);
    background: linear-gradient(to bottom, var(--color-border) 1px, transparent 1px) 0 25%/100% 25%;
    border-bottom: 1px solid var(--color-border);
}
.finance-performance__month {
    display: grid;
    min-width: 0;
    grid-template-rows: 1fr auto auto;
    gap: var(--space-2);
    text-align: center;
}
.finance-performance__bars {
    display: flex;
    height: 9rem;
    align-items: flex-end;
    justify-content: center;
    gap: 0.35rem;
}
.finance-performance__bar {
    width: min(1.3rem, 38%);
    min-height: 0;
    border-radius: 0.3rem 0.3rem 0 0;
    transition: height var(--duration-normal) var(--ease-standard);
}
.finance-performance__month > strong {
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
    text-transform: capitalize;
}
.finance-performance__month > small {
    overflow: hidden;
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-performance__totals {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: var(--space-5);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.finance-performance__totals span {
    display: flex;
    align-items: baseline;
    gap: var(--space-2);
}
.finance-performance__totals strong {
    color: var(--color-brand);
    font-size: var(--font-size-base);
}
.finance-performance__cash-result {
    padding: var(--space-2) var(--space-3);
    background: var(--color-surface-secondary-soft);
    border-radius: var(--radius-md);
}
.finance-performance__result--negative,
.finance-performance__result--negative strong {
    color: var(--color-danger) !important;
}
.finance-automation__heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--space-4);
}
.finance-automation__heading h2 {
    margin-top: var(--space-1);
    color: var(--color-brand);
    font-size: var(--font-size-lg);
}
.finance-automation__heading p {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.finance-automation__rules {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(19rem, 1fr));
    gap: var(--space-3);
}
.finance-automation__rule {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    padding: var(--space-4);
    align-items: center;
    gap: var(--space-3);
    background: var(--color-surface-muted);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-automation__rule > div:nth-child(2) {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-automation__rule small {
    overflow: hidden;
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-automation__marker {
    padding: 0.4rem 0.65rem;
    color: var(--color-brand-secondary-active);
    background: var(--color-surface-secondary-soft);
    border-radius: 999px;
    font-size: var(--font-size-xs);
    font-weight: 700;
    white-space: nowrap;
}
.finance-automation__marker--inactive {
    color: var(--color-text-muted);
    background: var(--color-surface);
}
.finance-automation__state {
    color: var(--color-brand-secondary);
    font-size: var(--font-size-xs);
    font-weight: 700;
}
.finance-automation__actions {
    display: flex;
    grid-column: 2/-1;
    justify-content: flex-end;
    gap: var(--space-1);
    border-top: 1px solid var(--color-border);
    padding-top: var(--space-2);
}
.finance-automation__empty {
    padding: var(--space-4);
    color: var(--color-text-muted);
    text-align: center;
    background: var(--color-surface-muted);
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
}
.finance-rule__grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: var(--space-3);
}
.finance-rule__toggle {
    display: flex !important;
    padding: var(--space-3);
    flex-direction: row !important;
    align-items: flex-start;
    gap: var(--space-3) !important;
    background: var(--color-surface-secondary-soft);
    border: 1px solid color-mix(in srgb, var(--color-brand-secondary) 24%, var(--color-border));
    border-radius: var(--radius-md);
    cursor: pointer;
}
.finance-rule__toggle input {
    width: 1rem;
    height: 1rem;
    margin-top: 0.15rem;
    accent-color: var(--color-brand-secondary);
}
.finance-rule__toggle span {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-rule__toggle small {
    color: var(--color-text-muted);
    font-weight: 400;
}

@media (max-width: 900px) {
    .finance-forecast__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .finance-forecast__item {
        border-bottom: 1px solid var(--color-border);
    }
    .finance-forecast__heading {
        align-items: flex-start;
    }
    .finance-forecast__total {
        align-items: flex-start;
    }
}
@media (max-width: 560px) {
    .finance-forecast__grid {
        grid-template-columns: 1fr;
    }
    .finance-forecast__heading,
    .finance-performance__heading {
        flex-direction: column;
        align-items: flex-start;
    }
    .finance-performance__chart {
        overflow-x: auto;
        grid-template-columns: repeat(6, minmax(5rem, 1fr));
    }
    .finance-performance__totals {
        justify-content: flex-start;
    }
    .finance-rule__grid {
        grid-template-columns: 1fr;
    }
    .finance-automation__rules {
        grid-template-columns: 1fr;
    }
}

.finance-page__header h1 {
    margin-top: var(--space-1);
    color: var(--color-brand);
    font-size: clamp(2rem, 4vw, 2.5rem);
    line-height: 1.1;
}

.finance-page__eyebrow {
    letter-spacing: 0.06em;
}

.finance-summary-card {
    position: relative;
    min-height: 9.5rem;
    border-color: color-mix(in srgb, var(--summary-color) 22%, var(--color-border));
    box-shadow: 0 0.4rem 1.25rem rgb(53 37 27 / 0.07);
}

.finance-summary-card::before {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 3px;
    background: var(--summary-color);
    content: '';
}

.finance-summary-card :deep(.card__body) {
    display: grid;
    grid-template-columns: 2.75rem minmax(0, 1fr);
    padding: var(--space-5);
    align-items: flex-start;
    gap: var(--space-4);
}

.finance-summary-card--receivable {
    --summary-color: var(--color-brand-secondary);
    --summary-soft: var(--color-surface-secondary-soft);
}

.finance-summary-card--overdue {
    --summary-color: var(--color-danger);
    --summary-soft: var(--color-danger-soft);
}

.finance-summary-card--received {
    --summary-color: var(--color-success);
    --summary-soft: var(--color-success-soft);
}

.finance-summary-card__icon {
    display: grid;
    width: 2.75rem;
    height: 2.75rem;
    place-items: center;
    color: var(--summary-color);
    background: var(--summary-soft);
    border-radius: var(--radius-md);
}

.finance-summary-card__content {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.finance-summary-card__label {
    color: var(--color-text-soft) !important;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
}

.finance-summary-card__value {
    margin-top: var(--space-2);
    color: var(--summary-color) !important;
    font-size: clamp(1.45rem, 2.4vw, 1.85rem) !important;
    line-height: 1.15;
    letter-spacing: -0.025em;
}

.finance-summary-card__detail {
    margin-top: var(--space-2);
    color: var(--color-text-muted) !important;
    font-size: var(--font-size-xs);
}
.finance-summary-card__action {
    align-self: flex-start;
    margin-top: var(--space-3);
}
.finance-overdue-clients {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-overdue-clients__intro {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    padding: var(--space-4);
    align-items: center;
    gap: var(--space-4);
    color: var(--color-brand);
    background: linear-gradient(110deg, var(--color-danger-soft), var(--color-surface));
    border: 1px solid color-mix(in srgb, var(--color-danger) 22%, var(--color-border));
    border-radius: var(--radius-lg);
}
.finance-overdue-clients__intro h3,
.finance-overdue-clients__intro p {
    margin: 0;
}
.finance-overdue-clients__intro h3 {
    font-size: var(--font-size-base);
}
.finance-overdue-clients__intro p {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.finance-overdue-clients__intro > strong {
    color: var(--color-danger);
    font-size: var(--font-size-xl);
}
.finance-overdue-clients__icon {
    display: grid;
    width: 3rem;
    height: 3rem;
    place-items: center;
    color: var(--color-danger);
    background: var(--color-surface);
    border-radius: var(--radius-lg);
}
.finance-overdue-clients__list {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}
.finance-overdue-clients__item {
    display: grid;
    grid-template-columns: minmax(12rem, 1.5fr) repeat(3, minmax(7rem, 0.65fr));
    padding: var(--space-4);
    align-items: center;
    gap: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-overdue-clients__identity {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: var(--space-3);
}
.finance-overdue-clients__identity > span {
    display: grid;
    width: 2.5rem;
    height: 2.5rem;
    flex: 0 0 auto;
    place-items: center;
    color: var(--neutral-0);
    background: var(--color-brand);
    border-radius: 50%;
    font-size: var(--font-size-xs);
    font-weight: 700;
}
.finance-overdue-clients__identity > div,
.finance-overdue-clients__metric {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-overdue-clients__identity strong,
.finance-overdue-clients__identity small {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-overdue-clients__identity small,
.finance-overdue-clients__metric span,
.finance-overdue-clients__contact > span {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-overdue-clients__metric strong {
    color: var(--color-brand);
    font-size: var(--font-size-sm);
}
.finance-overdue-clients__metric:nth-of-type(2) strong {
    color: var(--color-danger);
}
.finance-overdue-clients__contact {
    display: flex;
    grid-column: 1/-1;
    padding-top: var(--space-3);
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    border-top: 1px solid var(--color-border);
}

.finance-aging {
    display: flex;
    padding: var(--space-5);
    flex-direction: column;
    gap: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: 0 0.3rem 1rem rgb(53 37 27 / 0.05);
}
.finance-aging__heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: var(--space-4);
}
.finance-aging__heading h2,
.finance-aging__heading p {
    margin: 0;
}
.finance-aging__heading h2 {
    margin-top: var(--space-1);
    color: var(--color-brand);
    font-size: var(--font-size-lg);
}
.finance-aging__heading p {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.finance-aging__grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-aging__item {
    position: relative;
    display: flex;
    min-width: 0;
    min-height: 7.5rem;
    padding: var(--space-4) !important;
    align-items: stretch !important;
    border: 0 !important;
    border-right: 1px solid var(--color-border) !important;
    border-radius: 0 !important;
    text-align: left;
}
.finance-aging__item::before {
    position: absolute;
    top: 0;
    right: var(--space-4);
    left: var(--space-4);
    height: 2px;
    background: transparent;
    border-radius: 0 0 2px 2px;
    content: '';
    transition: background-color var(--duration-fast) var(--ease-standard);
}
.finance-aging__item:hover::before,
.finance-aging__item--active::before {
    background: var(--color-brand-secondary);
}
.finance-aging__item :deep(.app-button__label) {
    display: block;
    width: 100%;
    min-width: 0;
}
.finance-aging__item:last-child {
    border-right: 0 !important;
}
.finance-aging__content {
    display: flex;
    width: 100%;
    min-width: 0;
    height: 100%;
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-2);
}
.finance-aging__label {
    min-height: 1rem;
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    line-height: 1.25;
}
.finance-aging__value {
    max-width: 100%;
    overflow: hidden;
    color: var(--color-brand);
    font-size: clamp(1.05rem, 1.8vw, 1.3rem);
    line-height: 1.15;
    letter-spacing: -0.02em;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-aging__count {
    display: flex;
    margin-top: auto;
    align-items: center;
    gap: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-regular);
}
.finance-aging__count > span {
    display: inline-grid;
    min-width: 1.35rem;
    height: 1.35rem;
    padding: 0 0.3rem;
    place-items: center;
    color: var(--color-brand-secondary-active);
    background: var(--color-surface-secondary-soft);
    border-radius: 999px;
    font-weight: var(--font-weight-semibold);
}
.finance-aging__item--active {
    background: var(--color-surface-secondary-soft) !important;
    box-shadow: inset 0 -3px var(--color-brand-secondary);
}
.finance-aging__item--active .finance-aging__label,
.finance-aging__item--active .finance-aging__value {
    color: var(--color-brand-secondary-active);
}
.finance-aging__item--active .finance-aging__count > span {
    color: var(--neutral-0);
    background: var(--color-brand-secondary);
}

.finance-page__filters {
    display: grid;
    grid-template-columns: minmax(11rem, 1.25fr) minmax(12rem, 1.5fr) minmax(11rem, 1fr);
    padding: var(--space-4);
    gap: var(--space-3);
    align-items: end;
    background: var(--color-surface-muted);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.finance-page__filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
}

.finance-page__filter-count {
    padding: 0.35rem 0.65rem;
    color: var(--color-brand-secondary);
    background: var(--color-surface-secondary-soft);
    border-radius: 999px;
    font-size: var(--font-size-xs);
    font-weight: 700;
    white-space: nowrap;
}
.finance-page__section-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.finance-page__field-help {
    color: var(--color-brand-secondary);
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.finance-invoice-table :deep(.app-table) {
    min-width: 58rem;
    table-layout: fixed;
}
.finance-invoice-table :deep(.app-table__header:nth-child(1)) {
    width: 25%;
}
.finance-invoice-table :deep(.app-table__header:nth-child(2)) {
    width: 7%;
}
.finance-invoice-table :deep(.app-table__header:nth-child(3)) {
    width: 14%;
}
.finance-invoice-table :deep(.app-table__header:nth-child(4)) {
    width: 14%;
}
.finance-invoice-table :deep(.app-table__header:nth-child(5)) {
    width: 40%;
}
.finance-invoice-table :deep(.app-table__cell) {
    padding-top: var(--space-4);
    padding-bottom: var(--space-4);
}
.finance-invoice-table__identity {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-invoice-table__identity > strong {
    overflow: hidden;
    color: var(--color-brand);
    font-size: var(--font-size-base);
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-invoice-table__identity > span {
    overflow: hidden;
    color: var(--color-text);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-invoice-table__identity > small {
    overflow: hidden;
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-invoice-table__installment {
    display: inline-flex;
    min-width: 2.5rem;
    height: 2.5rem;
    align-items: baseline;
    justify-content: center;
    color: var(--color-brand);
    background: var(--color-surface-muted);
    border: 1px solid var(--color-border);
    border-radius: 999px;
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    line-height: 2.35rem;
}
.finance-invoice-table__installment small {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-invoice-table__due,
.finance-invoice-table__balance {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}
.finance-invoice-table__due {
    align-items: flex-start;
}
.finance-invoice-table__due > strong {
    color: var(--color-text);
    font-size: var(--font-size-sm);
    white-space: nowrap;
}
.finance-invoice-table__due > small {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
    line-height: 1.35;
}
.finance-invoice-table__due > .finance-invoice-table__uncontacted {
    color: var(--color-danger);
    font-weight: var(--font-weight-semibold);
}
.finance-invoice-table__balance {
    align-items: flex-end;
}
.finance-invoice-table__balance > strong {
    color: var(--color-brand);
    font-size: var(--font-size-base);
    white-space: nowrap;
}
.finance-invoice-table__balance > small {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
    white-space: nowrap;
}
.finance-invoice-table__actions {
    display: flex;
    align-items: flex-end;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-invoice-table__primary-actions,
.finance-invoice-table__secondary-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-1);
}
.finance-invoice-table__secondary-actions :deep(.btn) {
    min-height: 1.8rem;
    padding-top: var(--space-1);
    padding-bottom: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-invoice-table__secondary-actions :deep(.btn:hover) {
    color: var(--color-brand);
}
.finance-invoice-table__delete :deep(.app-button__label) {
    color: var(--color-danger);
}
.finance-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: var(--space-3);
    gap: var(--space-3);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.finance-pagination p {
    margin: 0;
}
.finance-pagination > div {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}
.finance-pagination strong {
    color: var(--color-brand);
}

.finance-details {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-details h3,
.finance-details p {
    margin: 0;
}
.finance-details section {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}
.finance-details__hero {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    padding: var(--space-5);
    overflow: hidden;
    align-items: center;
    gap: var(--space-4);
    color: var(--neutral-0);
    background: linear-gradient(
        125deg,
        var(--color-brand),
        color-mix(in srgb, var(--color-brand) 72%, var(--color-brand-secondary))
    );
    border-radius: var(--radius-lg);
}
.finance-details__hero::after {
    position: absolute;
    right: -3rem;
    bottom: -5rem;
    width: 11rem;
    height: 11rem;
    background: color-mix(in srgb, var(--color-highlight) 18%, transparent);
    border-radius: 50%;
    content: '';
}
.finance-details__hero-icon {
    display: grid;
    width: 3.25rem;
    height: 3.25rem;
    z-index: 1;
    place-items: center;
    color: var(--color-highlight);
    background: rgb(255 255 255 / 0.1);
    border: 1px solid rgb(255 255 255 / 0.18);
    border-radius: var(--radius-lg);
}
.finance-details__hero-copy,
.finance-details__hero-balance {
    display: flex;
    z-index: 1;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-details__hero-copy > span,
.finance-details__hero-copy > small,
.finance-details__hero-balance > span:first-child {
    color: rgb(255 255 255 / 0.7);
    font-size: var(--font-size-xs);
}
.finance-details__hero-copy > strong {
    font-size: var(--font-size-lg);
}
.finance-details__hero-balance {
    align-items: flex-end;
}
.finance-details__hero-balance > strong {
    font-size: var(--font-size-xl);
}
.finance-details__hero .finance-page__status {
    margin-top: var(--space-1);
    color: var(--color-brand);
    background: var(--neutral-0);
}
.finance-details__summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin: 0;
    gap: var(--space-3);
}
.finance-details__summary > div {
    padding: var(--space-3);
    background: var(--color-surface-muted);
    border-radius: var(--radius-md);
}
.finance-details__summary dt {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-details__summary dd {
    margin: var(--space-1) 0 0;
    font-weight: 700;
}
.finance-details__panel {
    padding: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-details__panel--agreement {
    background: var(--color-surface-highlight-soft);
    border-color: color-mix(in srgb, var(--color-highlight) 30%, var(--color-border));
}
.finance-details__panel--payments {
    border-top: 3px solid var(--color-brand-secondary);
}
.finance-details__panel--reminders {
    border-top: 3px solid var(--color-brand);
}
.finance-details__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-3) 0;
    border-bottom: 1px solid var(--color-border);
}
.finance-details__row > div {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-details__row small,
.finance-details section > small {
    color: var(--color-text-muted);
}
.finance-details__row > span {
    font-weight: 700;
    white-space: nowrap;
}
.finance-details__section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
}
.finance-details__section-heading > div {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}
.finance-details__section-heading > strong {
    color: var(--color-brand-secondary);
    font-size: var(--font-size-sm);
}
.finance-details__section-icon {
    display: grid;
    width: 1.85rem;
    height: 1.85rem;
    place-items: center;
    color: var(--color-brand);
    background: var(--color-surface-soft);
    border-radius: var(--radius-sm);
}
.finance-details__channel {
    padding: 0.3rem 0.55rem;
    color: var(--color-brand-secondary-active);
    background: var(--color-surface-secondary-soft);
    border-radius: 999px;
    font-size: var(--font-size-xs);
}
.finance-details__channel--scheduled {
    color: var(--color-brand);
    background: var(--color-highlight-soft, var(--color-surface-muted));
}
.finance-details__channel--failed {
    color: var(--color-danger);
    background: var(--color-danger-soft);
}
.finance-details__row--cancelled {
    opacity: 0.72;
}
.finance-details__row--cancelled .finance-details__payment-value > span {
    text-decoration: line-through;
}
.finance-details__cancelled-tag {
    display: inline-flex;
    margin-left: var(--space-1);
    padding: 0.15rem 0.4rem;
    color: var(--color-danger);
    background: var(--color-danger-soft);
    border-radius: 999px;
    font-size: var(--font-size-xs);
    text-decoration: none;
}
.finance-details__payment-value {
    display: flex;
    align-items: flex-end;
    flex-direction: column;
    gap: var(--space-1);
    font-weight: 700;
    white-space: nowrap;
}
.finance-details__payment-actions {
    display: flex;
    align-items: center;
    gap: var(--space-1);
}
.finance-details__empty {
    padding: var(--space-3);
    color: var(--color-text-muted);
    background: var(--color-surface-muted);
    border-radius: var(--radius-md);
}
.finance-details__cancellation {
    padding: var(--space-3);
    color: var(--color-danger);
    background: var(--color-danger-soft);
    border-radius: var(--radius-md);
}
.finance-details__totals {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-details__totals span {
    display: flex;
    flex-direction: column;
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-details__totals span {
    padding: var(--space-3) var(--space-4);
    border-right: 1px solid var(--color-border);
}
.finance-details__totals span:last-child {
    background: var(--color-surface-secondary-soft);
    border-right: 0;
}
.finance-details__totals strong {
    color: var(--color-brand);
    font-size: var(--font-size-base);
}

.finance-cancel {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-cancel__intro {
    display: grid;
    grid-template-columns: 3rem minmax(0, 1fr);
    align-items: start;
    gap: var(--space-3);
}
.finance-cancel__intro h3,
.finance-cancel__intro p {
    margin: 0;
}
.finance-cancel__intro h3 {
    color: var(--color-brand);
    font-size: var(--font-size-base);
}
.finance-cancel__intro p {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
    line-height: 1.5;
}
.finance-cancel__icon {
    display: grid;
    width: 3rem;
    height: 3rem;
    place-items: center;
    color: var(--color-danger);
    background: var(--color-danger-soft);
    border: 1px solid color-mix(in srgb, var(--color-danger) 20%, transparent);
    border-radius: var(--radius-lg);
}
.finance-cancel__summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin: 0;
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-cancel__summary > div {
    padding: var(--space-3) var(--space-4);
    border-right: 1px solid var(--color-border);
    border-bottom: 1px solid var(--color-border);
}
.finance-cancel__summary > div:nth-child(2n) {
    border-right: 0;
}
.finance-cancel__summary > div:nth-last-child(-n + 2) {
    border-bottom: 0;
}
.finance-cancel__summary dt {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-cancel__summary dd {
    margin: var(--space-1) 0 0;
    overflow: hidden;
    color: var(--color-text);
    font-size: var(--font-size-sm);
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-cancel__summary > div:last-child dd {
    color: var(--color-danger);
}
.finance-cancel__impact {
    display: flex;
    padding: var(--space-3) var(--space-4);
    flex-direction: column;
    gap: var(--space-1);
    color: var(--color-brand-secondary-active);
    background: var(--color-surface-secondary-soft);
    border-left: 3px solid var(--color-brand-secondary);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
}
.finance-cancel__impact span {
    color: var(--color-text-muted);
    line-height: 1.45;
}
.finance-cancel__reason :deep(textarea) {
    min-height: 7rem;
    resize: vertical;
}
.finance-cancel__reason :deep(.app-field__hint) {
    align-self: flex-end;
}
.finance-cancel__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
    padding-top: var(--space-1);
}

.finance-payment {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-payment__intro {
    display: grid;
    grid-template-columns: 3rem minmax(0, 1fr);
    align-items: start;
    gap: var(--space-3);
}
.finance-payment__intro h3,
.finance-payment__intro p {
    margin: 0;
}
.finance-payment__intro h3 {
    color: var(--color-brand);
    font-size: var(--font-size-base);
}
.finance-payment__intro p {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
    line-height: 1.5;
}
.finance-payment__icon {
    display: grid;
    width: 3rem;
    height: 3rem;
    place-items: center;
    color: var(--color-brand-secondary);
    background: var(--color-surface-secondary-soft);
    border: 1px solid color-mix(in srgb, var(--color-brand-secondary) 20%, transparent);
    border-radius: var(--radius-lg);
}
.finance-payment__summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin: 0;
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-payment__summary > div {
    padding: var(--space-3) var(--space-4);
    border-right: 1px solid var(--color-border);
    border-bottom: 1px solid var(--color-border);
}
.finance-payment__summary > div:nth-child(2n) {
    border-right: 0;
}
.finance-payment__summary > div:nth-last-child(-n + 2) {
    border-bottom: 0;
}
.finance-payment__summary dt {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-payment__summary dd {
    margin: var(--space-1) 0 0;
    overflow: hidden;
    color: var(--color-text);
    font-size: var(--font-size-sm);
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-payment__summary > div:last-child dd {
    color: var(--color-brand-secondary);
}
.finance-installment {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-installment__intro {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
}
.finance-installment__intro h3,
.finance-installment__intro p {
    margin: 0;
}
.finance-installment__intro h3 {
    color: var(--color-brand);
    font-size: var(--font-size-base);
}
.finance-installment__intro p {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
    line-height: 1.45;
}
.finance-installment__icon {
    display: grid;
    width: 3rem;
    height: 3rem;
    flex: 0 0 auto;
    place-items: center;
    color: var(--color-brand);
    background: var(--color-surface-soft);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}
.finance-installment__summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin: 0;
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-installment__summary > div {
    min-width: 0;
    padding: var(--space-3) var(--space-4);
    border-right: 1px solid var(--color-border);
    border-bottom: 1px solid var(--color-border);
}
.finance-installment__summary > div:nth-child(2n) {
    border-right: 0;
}
.finance-installment__summary > div:nth-last-child(-n + 2) {
    border-bottom: 0;
}
.finance-installment__summary dt {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-installment__summary dd {
    margin: var(--space-1) 0 0;
    overflow: hidden;
    color: var(--color-text);
    font-size: var(--font-size-sm);
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.finance-installment__summary > div:last-child dd {
    color: var(--color-highlight);
}
.finance-installment__fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}
.finance-installment__wide {
    grid-column: 1/-1;
}
.finance-installment__result {
    display: flex;
    padding: var(--space-3) var(--space-4);
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    color: var(--color-text-soft);
    background: var(--color-surface-highlight-soft);
    border-left: 3px solid var(--color-highlight);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
}
.finance-installment__result strong {
    color: var(--color-brand);
    font-size: var(--font-size-lg);
}
.finance-installment__result--invalid {
    color: var(--color-danger);
    background: var(--color-danger-soft);
    border-left-color: var(--color-danger);
}
.finance-installment__result--invalid strong {
    color: var(--color-danger);
}
.finance-installment__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
}
.finance-payment__fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
}
.finance-payment__fields > * {
    min-width: 0;
}
.finance-payment__wide {
    grid-column: 1 / -1;
}
.finance-payment__remaining {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-3) var(--space-4);
    gap: var(--space-3);
    color: var(--color-brand-secondary-active);
    background: var(--color-surface-secondary-soft);
    border-left: 3px solid var(--color-brand-secondary);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
}
.finance-payment__remaining strong {
    font-size: var(--font-size-base);
    white-space: nowrap;
}
.finance-payment__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
    padding-top: var(--space-1);
}

.finance-edit {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-edit__intro {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-3) var(--space-4);
    gap: var(--space-3);
    background: var(--color-surface-muted);
    border-radius: var(--radius-md);
}
.finance-edit__intro > div {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}
.finance-edit__intro span {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-edit__intro h3 {
    margin: 0;
    color: var(--color-brand);
    font-size: var(--font-size-base);
}
.finance-edit__fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}
.finance-edit__fields > * {
    min-width: 0;
}
.finance-edit__balance {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 var(--space-3);
    color: var(--color-text-muted);
    background: var(--color-surface-secondary-soft);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
}
.finance-edit__balance strong {
    color: var(--color-brand-secondary);
}
.finance-edit__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
}

.finance-reminder {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}
.finance-reminder__intro {
    display: grid;
    grid-template-columns: 3rem minmax(0, 1fr);
    align-items: start;
    gap: var(--space-3);
}
.finance-reminder__intro h3,
.finance-reminder__intro p {
    margin: 0;
}
.finance-reminder__intro h3 {
    color: var(--color-brand);
    font-size: var(--font-size-base);
}
.finance-reminder__intro p {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
    line-height: 1.5;
}
.finance-reminder__icon {
    display: grid;
    width: 3rem;
    height: 3rem;
    place-items: center;
    color: var(--color-brand-secondary);
    background: var(--color-surface-secondary-soft);
    border: 1px solid color-mix(in srgb, var(--color-brand-secondary) 20%, transparent);
    border-radius: var(--radius-lg);
}
.finance-reminder__summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin: 0;
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.finance-reminder__summary > div {
    padding: var(--space-3) var(--space-4);
}
.finance-reminder__summary > div + div {
    border-left: 1px solid var(--color-border);
}
.finance-reminder__summary dt {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.finance-reminder__summary dd {
    margin: var(--space-1) 0 0;
    color: var(--color-brand);
    font-size: var(--font-size-sm);
    font-weight: 700;
}
.finance-reminder__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
}

@media (max-width: 960px) {
    .finance-payables__filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .finance-payables__list article {
        grid-template-columns: minmax(10rem, 1fr) auto auto;
    }
    .finance-payables__actions {
        grid-column: 1/-1;
    }
    .finance-page__filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .finance-aging__grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .finance-aging__item {
        border-bottom: 1px solid var(--color-border) !important;
    }
    .finance-aging__item:nth-child(3n) {
        border-right: 0 !important;
    }
    .finance-aging__item:nth-last-child(-n + 2) {
        border-bottom: 0 !important;
    }
    .finance-overdue-clients__item {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .finance-overdue-clients__identity {
        grid-column: 1/-1;
    }
}

@media (max-width: 600px) {
    .finance-payables__filters,
    .finance-payables__list article {
        grid-template-columns: 1fr;
    }
    .finance-payables__filter-actions,
    .finance-payables__actions {
        justify-content: stretch;
    }
    .finance-payables__summary {
        grid-template-columns: 1fr;
    }
    .finance-payables__summary span {
        border-right: 0;
        border-bottom: 1px solid var(--color-border);
    }
    .finance-payables__summary span:last-child {
        border-bottom: 0;
    }
    .finance-payables__filter-actions > *,
    .finance-payables__list article > .finance-payables__actions > * {
        width: 100%;
    }
    .finance-payables__list article > .finance-payables__actions {
        flex-direction: column;
        align-items: stretch;
    }
    .finance-payables__actions .finance-payables__status {
        width: auto;
        margin: 0 0 var(--space-1);
        align-self: flex-start;
    }
    .finance-payable-details__actions,
    .finance-payable-details__actions > div {
        align-items: stretch;
        flex-direction: column;
    }
    .finance-page__filters {
        grid-template-columns: 1fr;
    }

    .finance-aging__heading {
        align-items: flex-start;
        flex-direction: column;
    }
    .finance-aging__grid {
        grid-template-columns: 1fr;
    }
    .finance-aging__item,
    .finance-aging__item:nth-child(3n),
    .finance-aging__item:nth-last-child(-n + 2) {
        border-right: 0 !important;
        border-bottom: 1px solid var(--color-border) !important;
    }
    .finance-aging__item:last-child {
        border-bottom: 0 !important;
    }

    .finance-page__filter-actions {
        justify-content: stretch;
    }

    .finance-page__section-heading {
        align-items: stretch;
        flex-direction: column;
    }
    .finance-page__section-actions {
        justify-content: space-between;
    }
    .finance-pagination {
        align-items: stretch;
        flex-direction: column;
    }
    .finance-pagination > div {
        justify-content: space-between;
    }

    .finance-page__filter-actions > * {
        flex: 1;
    }

    .finance-details__summary {
        grid-template-columns: 1fr;
    }

    .finance-details__hero {
        grid-template-columns: auto minmax(0, 1fr);
    }
    .finance-details__hero-balance {
        grid-column: 1/-1;
        padding-top: var(--space-3);
        align-items: flex-start;
        border-top: 1px solid rgb(255 255 255 / 0.16);
    }
    .finance-details__totals {
        grid-template-columns: 1fr;
    }
    .finance-details__totals span {
        border-right: 0;
        border-bottom: 1px solid var(--color-border);
    }
    .finance-details__totals span:last-child {
        border-bottom: 0;
    }
    .finance-details__section-heading {
        align-items: flex-start;
    }
    .finance-overdue-clients__intro {
        grid-template-columns: auto minmax(0, 1fr);
    }
    .finance-overdue-clients__intro > strong {
        grid-column: 1/-1;
    }
    .finance-overdue-clients__item {
        grid-template-columns: 1fr 1fr;
    }
    .finance-overdue-clients__identity {
        grid-column: 1/-1;
    }
    .finance-overdue-clients__metric:nth-of-type(4) {
        grid-column: 1/-1;
    }
    .finance-overdue-clients__contact {
        align-items: stretch;
        flex-direction: column;
    }

    .finance-cancel__summary {
        grid-template-columns: 1fr;
    }

    .finance-cancel__summary > div,
    .finance-cancel__summary > div:nth-child(2n),
    .finance-cancel__summary > div:nth-last-child(-n + 2) {
        border-right: 0;
        border-bottom: 1px solid var(--color-border);
    }

    .finance-cancel__summary > div:last-child {
        border-bottom: 0;
    }

    .finance-cancel__actions {
        flex-direction: column-reverse;
    }

    .finance-payment__summary,
    .finance-payment__fields,
    .finance-edit__fields {
        grid-template-columns: 1fr;
    }

    .finance-payment__summary > div,
    .finance-payment__summary > div:nth-child(2n),
    .finance-payment__summary > div:nth-last-child(-n + 2) {
        border-right: 0;
        border-bottom: 1px solid var(--color-border);
    }

    .finance-payment__summary > div:last-child {
        border-bottom: 0;
    }

    .finance-payment__actions {
        flex-direction: column-reverse;
    }

    .finance-installment__summary,
    .finance-installment__fields {
        grid-template-columns: 1fr;
    }

    .finance-installment__summary > div,
    .finance-installment__summary > div:nth-child(2n),
    .finance-installment__summary > div:nth-last-child(-n + 2) {
        border-right: 0;
        border-bottom: 1px solid var(--color-border);
    }

    .finance-installment__summary > div:last-child {
        border-bottom: 0;
    }
    .finance-installment__wide {
        grid-column: auto;
    }
    .finance-installment__actions {
        flex-direction: column-reverse;
    }

    .finance-edit__actions {
        flex-direction: column-reverse;
    }
    .finance-reminder__summary {
        grid-template-columns: 1fr;
    }
    .finance-reminder__summary > div + div {
        border-top: 1px solid var(--color-border);
        border-left: 0;
    }
    .finance-reminder__actions {
        flex-direction: column-reverse;
    }
}
</style>
