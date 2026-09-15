<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Payment;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowApiTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Client $client;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->travelTo(now()->setDate(2026, 9, 15)->startOfDay());
        $this->organization = Organization::where('slug', OrganizationSeeder::DEFAULT_SLUG)->firstOrFail();
        $this->client = $this->organization->clients()->create(['name' => 'Cliente caixa', 'document' => '12345678901']);
    }

    public function test_realizado_por_pagamento_e_projecao_apenas_do_saldo_atual(): void
    {
        $invoice = $this->invoice(['total_cents' => 10000, 'paid_cents' => 4000, 'balance_cents' => 6000, 'status' => 'partial']);
        $this->receipt($invoice, '2026-09-01 00:00:00', 4000);
        $outsideDue = $this->invoice(['due_on' => '2026-10-01', 'balance_cents' => 0, 'status' => 'paid']);
        $this->receipt($outsideDue, '2026-09-30 23:59:59', 2000);
        $this->receipt($outsideDue, '2026-10-01 00:00:00', 500);
        $this->receipt($outsideDue, '2026-08-31 23:59:59', 500);
        $this->receipt($invoice, '2026-09-10 12:00:00', 900, ['cancelled_at' => now()]);
        $this->invoice(['status' => 'draft']);
        $this->invoice(['status' => 'cancelled']);
        $payable = $this->payable(['amount_cents' => 5000, 'paid_cents' => 1000, 'balance_cents' => 4000, 'status' => 'partial']);
        PayablePayment::create(['organization_id' => $this->organization->id, 'payable_id' => $payable->id, 'paid_at' => '2026-09-02', 'amount_cents' => 1000, 'method' => 'pix']);
        PayablePayment::create(['organization_id' => $this->organization->id, 'payable_id' => $payable->id, 'paid_at' => '2026-09-02', 'amount_cents' => 999, 'method' => 'pix', 'cancelled_at' => now()]);
        $this->payable(['status' => 'cancelled']);
        $this->payable(['due_on' => '2026-10-01']);

        $this->asTenant()->getJson('/api/finance/cash-flow?month=2026-09')->assertOk()
            ->assertJsonPath('realized.in_cents', 6000)->assertJsonPath('realized.out_cents', 1000)
            ->assertJsonPath('realized.net_cents', 5000)->assertJsonPath('projected.in_cents', 6000)
            ->assertJsonPath('projected.out_cents', 4000)->assertJsonPath('combined_net_cents', 7000)
            ->assertJsonPath('indicators.cash_margin_percent', 83.33)->assertJsonPath('indicators.overdue_percent', 60)
            ->assertJsonPath('indicators.largest_client_percent', 100)->assertJsonCount(5, 'entries')
            ->assertJsonPath('monthly.0.realized.in_cents', 6000)->assertJsonPath('period.to', '2026-09-30');
    }

    public function test_periodo_customizado_inclui_meses_vazios_e_exporta_todas_as_paginas(): void
    {
        for ($i = 0; $i < 27; $i++) {
            $this->payable(['supplier' => $i === 26 ? '=TESTE' : 'Fornecedor '.$i]);
        }
        $this->payable(['supplier' => 'FORA DO PERIODO', 'due_on' => '2026-12-01']);
        $query = 'from=2026-08-31&to=2026-10-01';
        $this->asTenant()->getJson('/api/finance/cash-flow?'.$query.'&page=2')->assertOk()
            ->assertJsonCount(2, 'entries')->assertJsonPath('pagination.total', 27)
            ->assertJsonCount(3, 'monthly')->assertJsonPath('monthly.0.projected.out_cents', 0)
            ->assertJsonPath('projected.out_cents', 270000)->assertJsonPath('indicators.cash_margin_percent', null);
        $csv = $this->asTenant()->get('/api/finance/cash-flow/export?'.$query.'&page=2')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=TESTE", $csv);
        $this->assertStringContainsString('Fornecedor 0', $csv);
        $this->assertStringContainsString('Fornecedor 25', $csv);
        $this->assertStringNotContainsString('FORA DO PERIODO', $csv);
        $this->assertStringContainsString('2026-08-31', $csv);
    }

    public function test_isola_escritorios_em_todas_as_fontes_e_na_exportacao(): void
    {
        $other = Organization::where('slug', OrganizationSeeder::SECONDARY_SLUG)->firstOrFail();
        $otherClient = $other->clients()->create(['name' => 'CLIENTE SIGILOSO', 'document' => '10987654321']);
        $invoice = $this->invoice(['organization_id' => $other->id, 'client_id' => $otherClient->id]);
        $this->receipt($invoice, '2026-09-10', 1000, ['organization_id' => $other->id]);
        $payable = $this->payable(['organization_id' => $other->id, 'supplier' => 'FORNECEDOR SIGILOSO']);
        PayablePayment::create(['organization_id' => $other->id, 'payable_id' => $payable->id, 'paid_at' => '2026-09-10', 'amount_cents' => 1000, 'method' => 'pix']);
        $this->asTenant()->getJson('/api/finance/cash-flow')->assertOk()->assertJsonCount(0, 'entries')
            ->assertJsonPath('combined_net_cents', 0)->assertJsonPath('indicators.largest_client', null);
        $csv = $this->asTenant()->get('/api/finance/cash-flow/export')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('SIGILOSO', $csv);
    }

    public function test_valida_periodos_e_paginacao_tambem_na_exportacao(): void
    {
        foreach (['month=2026-13', 'from=2026-09-01', 'from=2026-09-02&to=2026-09-01', 'from=2025-01-01&to=2026-01-02', 'month=2026-09&from=2026-09-01&to=2026-09-30', 'page=0'] as $query) {
            foreach (['', '/export'] as $suffix) {
                $this->asTenant()->getJson('/api/finance/cash-flow'.$suffix.'?'.$query)->assertUnprocessable();
            }
        }
        $this->asTenant()->getJson('/api/finance/cash-flow?month=2024-02')->assertOk()->assertJsonPath('period.to', '2024-02-29');
        $this->asTenant()->getJson('/api/finance/cash-flow')->assertOk()->assertJsonPath('period.from', '2026-09-01');
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson('/api/finance/cash-flow')->assertUnauthorized();
        $this->getJson('/api/finance/cash-flow/export')->assertUnauthorized();
    }

    public function test_exige_permissao_financeira_na_consulta_e_exportacao(): void
    {
        $user = User::factory()->create();
        $this->organization->users()->attach($user->id, ['status' => 'active']);
        $this->withToken(auth('api')->login($user))->withHeader('X-Tenant', $this->organization->slug);
        $this->getJson('/api/finance/cash-flow')->assertForbidden();
        $this->getJson('/api/finance/cash-flow/export')->assertForbidden();
    }

    public function test_concentracao_agrupa_cobrancas_do_mesmo_cliente_e_margem_pode_ser_negativa(): void
    {
        $otherClient = $this->organization->clients()->create(['name' => 'Cliente B', 'document' => '10987654321']);
        $this->receipt($this->invoice(), '2026-09-10', 3000);
        $this->receipt($this->invoice(), '2026-09-11', 3000);
        $this->receipt($this->invoice(['client_id' => $otherClient->id]), '2026-09-12', 4000);
        $payable = $this->payable(['status' => 'paid', 'balance_cents' => 0]);
        PayablePayment::create(['organization_id' => $this->organization->id, 'payable_id' => $payable->id, 'paid_at' => '2026-09-10', 'amount_cents' => 12000, 'method' => 'pix']);
        $this->asTenant()->getJson('/api/finance/cash-flow?month=2026-09')->assertOk()
            ->assertJsonPath('indicators.largest_client.name', 'Cliente caixa')
            ->assertJsonPath('indicators.largest_client_percent', 60)
            ->assertJsonPath('indicators.cash_margin_percent', -20);
        $csv = $this->asTenant()->get('/api/finance/cash-flow/export?month=2026-09')->assertOk()->streamedContent();
        $this->assertStringContainsString('-20,00', $csv);
        $this->assertStringNotContainsString("'-20,00", $csv);
    }

    private function invoice(array $extra = []): Invoice
    {
        return Invoice::create(array_merge(['organization_id' => $this->organization->id, 'client_id' => $this->client->id, 'number' => 'CF-'.++$this->sequence, 'charge_identifier' => 'CF', 'due_on' => '2026-09-10', 'status' => 'open', 'subtotal_cents' => 10000, 'total_cents' => 10000, 'balance_cents' => 10000], $extra));
    }

    private function payable(array $extra = []): Payable
    {
        return Payable::create(array_merge(['organization_id' => $this->organization->id, 'supplier' => 'Fornecedor', 'description' => 'Serviço', 'due_on' => '2026-09-10', 'status' => 'open', 'amount_cents' => 10000, 'balance_cents' => 10000], $extra));
    }

    private function receipt(Invoice $invoice, string $date, int $amount, array $extra = []): Payment
    {
        return Payment::create(array_merge(['organization_id' => $this->organization->id, 'invoice_id' => $invoice->id, 'paid_at' => $date, 'amount_cents' => $amount, 'method' => 'pix'], $extra));
    }

    private function asTenant(): static
    {
        app(CurrentOrganization::class)->clear();
        $user = User::where('email', 'super-admin@legalis.local')->firstOrFail();

        return $this->withToken(auth('api')->login($user))->withHeader('X-Tenant', $this->organization->slug);
    }
}
