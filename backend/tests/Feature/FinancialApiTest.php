<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Folder;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Qualification;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialApiTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Folder $folder;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->organization = Organization::query()->where('slug', OrganizationSeeder::DEFAULT_SLUG)->firstOrFail();
        $this->folder = $this->organization->folders()->create(['name' => 'Pasta financeira']);
        $this->client = $this->organization->clients()->create([
            'name' => 'Cliente financeiro',
            'document' => '12345678901',
        ]);
        $this->folder->clients()->attach($this->client, [
            'qualification_id' => Qualification::query()->value('id'),
        ]);
    }

    public function test_cria_contrato_hibrido_para_cliente_da_pasta(): void
    {
        $this->asTenant()->postJson($this->url('fee-agreements'), [
            'client_id' => $this->client->id,
            'type' => 'hybrid',
            'status' => 'active',
            'hourly_rate_cents' => 30000,
            'contingency_percentage' => 10,
        ])->assertCreated()
            ->assertJsonPath('organization_id', $this->organization->id)
            ->assertJsonPath('client.name', 'Cliente financeiro');
    }

    public function test_contrato_rejeita_cliente_nao_vinculado_a_pasta(): void
    {
        $otherClient = $this->organization->clients()->create([
            'name' => 'Outro cliente',
            'document' => '10987654321',
        ]);

        $this->asTenant()->postJson($this->url('fee-agreements'), [
            'client_id' => $otherClient->id,
            'type' => 'fixed',
            'fixed_fee_cents' => 100000,
        ])->assertUnprocessable()->assertJsonValidationErrors('client_id');
    }

    public function test_cria_lista_e_atualiza_apontamento_de_tempo(): void
    {
        $entry = $this->asTenant()->postJson($this->url('time-entries'), [
            'worked_on' => '2026-09-08',
            'duration_minutes' => 90,
            'description' => 'Análise processual',
            'hourly_rate_cents' => 30000,
        ])->assertCreated()->assertJsonPath('status', 'open');

        $id = $entry->json('id');
        $this->asTenant()->patchJson($this->url("time-entries/{$id}"), [
            'duration_minutes' => 120,
        ])->assertOk()->assertJsonPath('duration_minutes', 120);
        $this->asTenant()->getJson($this->url('time-entries'))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $id);
    }

    public function test_cria_atualiza_e_exclui_despesa(): void
    {
        $expense = $this->asTenant()->postJson($this->url('expenses'), [
            'incurred_on' => '2026-09-08',
            'description' => 'Custas processuais',
            'amount_cents' => 18500,
        ])->assertCreated()->assertJsonPath('reimbursable', true);

        $id = $expense->json('id');
        $this->asTenant()->patchJson($this->url("expenses/{$id}"), [
            'amount_cents' => 19000,
        ])->assertOk()->assertJsonPath('amount_cents', 19000);
        $this->asTenant()->deleteJson($this->url("expenses/{$id}"))->assertNoContent();
    }

    public function test_recurso_financeiro_de_outra_pasta_retorna_404(): void
    {
        $entry = $this->asTenant()->postJson($this->url('time-entries'), [
            'worked_on' => '2026-09-08',
            'duration_minutes' => 30,
            'description' => 'Contato com cliente',
        ])->assertCreated();
        $otherFolder = $this->organization->folders()->create(['name' => 'Outra pasta']);

        $this->asTenant()->patchJson("/api/folders/{$otherFolder->id}/time-entries/{$entry->json('id')}", [
            'duration_minutes' => 60,
        ])->assertNotFound();
    }

    public function test_cria_cobranca_com_total_e_saldo_calculados(): void
    {
        $this->asTenant()->postJson('/api/invoices', [
            'client_id' => $this->client->id,
            'folder_id' => $this->folder->id,
            'due_on' => '2026-09-20',
            'subtotal_cents' => 150000,
            'discount_cents' => 10000,
        ])->assertCreated()
            ->assertJsonPath('total_cents', 140000)
            ->assertJsonPath('balance_cents', 140000)
            ->assertJsonPath('status', 'open');
    }

    public function test_cria_cobranca_parcelada_com_identificador_comum(): void
    {
        $response = $this->asTenant()->postJson('/api/invoices', [
            'client_id' => $this->client->id,
            'folder_id' => $this->folder->id,
            'due_on' => '2026-09-20',
            'subtotal_cents' => 10000,
            'discount_cents' => 1000,
            'installment_count' => 3,
        ])->assertCreated()
            ->assertJsonCount(3, 'installments')
            ->assertJsonPath('installments.0.installment_number', 1)
            ->assertJsonPath('installments.1.installment_number', 2)
            ->assertJsonPath('installments.2.installment_number', 3)
            ->assertJsonPath('installments.0.due_on', '2026-09-20T00:00:00.000000Z')
            ->assertJsonPath('installments.1.due_on', '2026-10-20T00:00:00.000000Z');

        $identifier = $response->json('charge_identifier');
        $this->assertNotEmpty($identifier);
        $this->assertDatabaseCount('invoices', 3);
        $this->assertSame(3, Invoice::query()->where('charge_identifier', $identifier)->count());
        $this->assertSame(10000, (int) Invoice::query()->where('charge_identifier', $identifier)->sum('subtotal_cents'));
        $this->assertSame(1000, (int) Invoice::query()->where('charge_identifier', $identifier)->sum('discount_cents'));
    }

    public function test_vincula_contrato_de_honorarios_a_todas_as_parcelas(): void
    {
        $agreement = $this->folder->feeAgreements()->create([
            'organization_id' => $this->organization->id,
            'client_id' => $this->client->id,
            'type' => 'fixed',
            'status' => 'active',
            'fixed_fee_cents' => 90000,
        ]);

        $response = $this->asTenant()->postJson('/api/invoices', [
            'client_id' => $this->client->id,
            'folder_id' => $this->folder->id,
            'fee_agreement_id' => $agreement->id,
            'due_on' => '2026-09-20',
            'subtotal_cents' => 90000,
            'installment_count' => 3,
        ])->assertCreated()->assertJsonPath('fee_agreement_id', $agreement->id);

        $this->assertSame(3, Invoice::query()
            ->where('charge_identifier', $response->json('charge_identifier'))
            ->where('fee_agreement_id', $agreement->id)
            ->count());
    }

    public function test_adiciona_parcela_a_cobranca_parcelada_existente(): void
    {
        $invoice = $this->asTenant()->postJson('/api/invoices', [
            'client_id' => $this->client->id,
            'folder_id' => $this->folder->id,
            'due_on' => '2026-09-20',
            'subtotal_cents' => 20000,
            'installment_count' => 2,
        ])->assertCreated();

        $this->asTenant()->postJson("/api/invoices/{$invoice->json('id')}/installments", [
            'due_on' => '2026-11-20',
            'subtotal_cents' => 5000,
        ])->assertCreated()
            ->assertJsonPath('charge_identifier', $invoice->json('charge_identifier'))
            ->assertJsonPath('installment_number', 3)
            ->assertJsonPath('installment_count', 3);

        $this->assertDatabaseHas('invoices', [
            'charge_identifier' => $invoice->json('charge_identifier'),
            'installment_number' => 3,
            'subtotal_cents' => 5000,
        ]);
        $this->assertSame(0, Invoice::query()
            ->where('charge_identifier', $invoice->json('charge_identifier'))
            ->where('installment_count', '!=', 3)
            ->count());
    }

    public function test_filtra_cobrancas_por_cliente_transacao_mes_e_periodo(): void
    {
        $septemberInvoice = $this->createInvoice(10000, '2026-09-20');
        $otherClient = $this->organization->clients()->create([
            'name' => 'Cliente de outubro',
            'document' => '98765432100',
        ]);
        $octoberInvoice = $this->asTenant()->postJson('/api/invoices', [
            'client_id' => $otherClient->id,
            'due_on' => '2026-10-15',
            'subtotal_cents' => 20000,
        ])->assertCreated();

        $this->asTenant()->getJson('/api/invoices?client_id='.$otherClient->id)
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $octoberInvoice->json('id'));
        $this->asTenant()->getJson('/api/invoices?transaction='.$septemberInvoice->json('charge_identifier'))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $septemberInvoice->json('id'));
        $this->asTenant()->getJson('/api/invoices?month=2026-09')
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $septemberInvoice->json('id'));
        $this->asTenant()->getJson('/api/invoices?due_from=2026-10-01&due_to=2026-10-31')
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $octoberInvoice->json('id'));
    }

    public function test_gera_cobranca_a_partir_de_horas_e_despesas_abertas(): void
    {
        $timeEntry = $this->asTenant()->postJson($this->url('time-entries'), [
            'worked_on' => '2026-09-10',
            'duration_minutes' => 60,
            'description' => 'Consultoria jurídica',
            'hourly_rate_cents' => 20000,
            'billable' => true,
        ])->assertCreated();
        $expense = $this->asTenant()->postJson($this->url('expenses'), [
            'incurred_on' => '2026-09-10',
            'description' => 'Emolumentos',
            'amount_cents' => 15000,
            'reimbursable' => true,
        ])->assertCreated();

        $invoice = $this->asTenant()->postJson($this->url('billing'), [
            'client_id' => $this->client->id,
            'due_on' => '2026-09-30',
            'time_entry_ids' => [$timeEntry->json('id')],
            'expense_ids' => [$expense->json('id')],
        ])->assertCreated()
            ->assertJsonPath('subtotal_cents', 35000)
            ->assertJsonPath('balance_cents', 35000);

        $this->assertDatabaseHas('time_entries', ['id' => $timeEntry->json('id'), 'invoice_id' => $invoice->json('id'), 'status' => 'billed']);
        $this->assertDatabaseHas('expenses', ['id' => $expense->json('id'), 'invoice_id' => $invoice->json('id'), 'status' => 'billed']);
        $this->asTenant()->getJson('/api/invoices/'.$invoice->json('id'))
            ->assertOk()
            ->assertJsonPath('time_entries.0.id', $timeEntry->json('id'))
            ->assertJsonPath('expenses.0.id', $expense->json('id'));

        $this->asTenant()->postJson('/api/invoices/'.$invoice->json('id').'/cancel', [
            'reason' => 'Cobrança emitida incorretamente',
        ])->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('balance_cents', 0)
            ->assertJsonPath('cancellation_reason', 'Cobrança emitida incorretamente');
        $this->assertDatabaseHas('time_entries', ['id' => $timeEntry->json('id'), 'invoice_id' => null, 'status' => 'open']);
        $this->assertDatabaseHas('expenses', ['id' => $expense->json('id'), 'invoice_id' => null, 'status' => 'open']);
    }

    public function test_pagamentos_atualizam_saldo_e_situacao_da_cobranca(): void
    {
        $invoice = $this->createInvoice(100000);

        $this->asTenant()->postJson("/api/invoices/{$invoice->json('id')}/payments", [
            'paid_at' => now()->toDateTimeString(),
            'amount_cents' => 40000,
            'method' => 'pix',
        ])->assertCreated();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->json('id'), 'paid_cents' => 40000, 'balance_cents' => 60000, 'status' => 'partial']);

        $this->asTenant()->postJson("/api/invoices/{$invoice->json('id')}/payments", [
            'paid_at' => now()->toDateTimeString(),
            'amount_cents' => 60000,
            'method' => 'bank_transfer',
        ])->assertCreated();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->json('id'), 'balance_cents' => 0, 'status' => 'paid']);
    }

    public function test_edita_cobranca_em_aberto_e_recalcula_o_saldo(): void
    {
        $invoice = $this->createInvoice(100000);

        $this->asTenant()->patchJson("/api/invoices/{$invoice->json('id')}", [
            'due_on' => '2026-10-15',
            'subtotal_cents' => 120000,
            'discount_cents' => 10000,
            'notes' => 'Condição renegociada',
        ])->assertOk()
            ->assertJsonPath('total_cents', 110000)
            ->assertJsonPath('balance_cents', 110000)
            ->assertJsonPath('notes', 'Condição renegociada');
    }

    public function test_pagamento_pode_ser_cancelado_sem_apagar_o_historico(): void
    {
        $invoice = $this->createInvoice(100000);
        $payment = $this->asTenant()->postJson("/api/invoices/{$invoice->json('id')}/payments", [
            'paid_at' => now()->toDateTimeString(),
            'amount_cents' => 40000,
            'method' => 'pix',
        ])->assertCreated();

        $this->asTenant()->postJson("/api/invoices/{$invoice->json('id')}/payments/{$payment->json('id')}/cancel", [
            'reason' => 'Pagamento registrado em duplicidade',
        ])->assertOk()
            ->assertJsonPath('cancellation_reason', 'Pagamento registrado em duplicidade')
            ->assertJsonPath('cancelled_by.name', 'Super Admin');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->json('id'),
            'amount_cents' => 40000,
            'cancellation_reason' => 'Pagamento registrado em duplicidade',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->json('id'),
            'paid_cents' => 0,
            'balance_cents' => 100000,
            'status' => 'open',
        ]);

        $this->asTenant()->postJson("/api/invoices/{$invoice->json('id')}/payments/{$payment->json('id')}/cancel", [
            'reason' => 'Novo cancelamento',
        ])->assertUnprocessable();
    }

    public function test_resumo_consolida_recebiveis_vencidos_e_recebidos_no_mes(): void
    {
        $invoice = $this->createInvoice(100000, '2020-01-01');
        $this->asTenant()->postJson("/api/invoices/{$invoice->json('id')}/payments", [
            'paid_at' => now()->toDateTimeString(),
            'amount_cents' => 25000,
            'method' => 'pix',
        ])->assertCreated();

        $this->asTenant()->getJson('/api/finance/summary')->assertOk()
            ->assertJsonPath('receivable_cents', 75000)
            ->assertJsonPath('overdue_cents', 75000)
            ->assertJsonPath('overdue_count', 1)
            ->assertJsonPath('received_this_month_cents', 25000);
    }

    private function createInvoice(int $amount, string $dueOn = '2026-09-20')
    {
        return $this->asTenant()->postJson('/api/invoices', [
            'client_id' => $this->client->id,
            'folder_id' => $this->folder->id,
            'due_on' => $dueOn,
            'subtotal_cents' => $amount,
        ])->assertCreated();
    }

    private function url(string $resource): string
    {
        return "/api/folders/{$this->folder->id}/{$resource}";
    }

    private function asTenant(): static
    {
        $user = User::query()->where('email', 'super-admin@legalis.local')->firstOrFail();

        return $this->withToken(auth('api')->login($user))->withHeader('X-Tenant', $this->organization->slug);
    }
}
