<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private Payable $payable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2028-02-15 12:00:00'));
        $this->seed(DatabaseSeeder::class);
        $this->organization = Organization::where('slug', OrganizationSeeder::DEFAULT_SLUG)->firstOrFail();
        $user = User::where('email', 'super-admin@legalis.local')->firstOrFail();
        $this->withToken(auth('api')->login($user))->withHeader('X-Tenant', $this->organization->slug);
        $this->payable = Payable::create(['organization_id' => $this->organization->id, 'supplier' => 'Locador', 'description' => 'Aluguel', 'due_on' => '2028-01-10', 'amount_cents' => 10000, 'balance_cents' => 10000, 'status' => 'open']);
    }

    private function payment(string $date = '2028-01-10'): int
    {
        return $this->postJson('/api/payables/'.$this->payable->id.'/payments', ['paid_at' => $date, 'amount_cents' => 1000, 'method' => 'pix'])->assertCreated()->json('id');
    }

    private function report(): array
    {
        return $this->getJson('/api/finance/reconciliation?month=2028-01')->assertOk()->json();
    }

    private function check(array $row, array $extra = []): void
    {
        $this->putJson('/api/finance/reconciliation/'.$row['kind'].'/'.$row['id'], $extra + [
            'observed_amount_cents' => $row['source']['amount_cents'], 'observed_on' => substr($row['source']['paid_at'], 0, 10),
            'reference' => 'Extrato conta operacional, linha 1', 'note' => 'Conferido com o extrato.', 'source_hash' => $row['source_hash'],
        ])->assertOk();
    }

    public function test_conferencia_nao_altera_saldo_e_registra_divergencia_e_correcao(): void
    {
        $id = $this->payment();
        $row = $this->report()['rows'][0];
        $this->check($row, ['observed_amount_cents' => 900]);
        $report = $this->report();
        $this->assertSame('divergent', $report['rows'][0]['status']);
        $this->assertSame(-100, $report['rows'][0]['difference_cents']);
        $this->assertFalse($report['can_close']);
        $this->check($row, ['note' => 'Corrigida leitura da linha do extrato.']);
        $this->assertSame('reconciled', $this->report()['rows'][0]['status']);
        $this->assertSame(1000, PayablePayment::findOrFail($id)->amount_cents);
        $this->assertSame(9000, $this->payable->fresh()->balance_cents);
        $this->assertDatabaseHas('financial_audit_events', ['action' => 'payment.created', 'source_key' => 'outgoing:'.$id]);
        $this->assertDatabaseHas('financial_audit_events', ['action' => 'reconciliation.updated', 'note' => 'Corrigida leitura da linha do extrato.']);
    }

    public function test_fecha_somente_periodo_conferido_e_sinaliza_estorno_posterior(): void
    {
        $id = $this->payment();
        $report = $this->report();
        $this->postJson('/api/finance/closing', ['month' => '2028-01', 'fingerprint' => $report['fingerprint'], 'note' => 'Fechar'])->assertUnprocessable();
        $this->check($report['rows'][0]);
        $report = $this->report();
        $this->postJson('/api/finance/closing', ['month' => '2028-01', 'fingerprint' => $report['fingerprint'], 'note' => 'Conferência mensal concluída.'])->assertOk();
        $this->assertSame('closed', $this->report()['state']);
        $this->postJson('/api/payables/'.$this->payable->id.'/payments/'.$id.'/cancel', ['reason' => 'Pagamento lançado em duplicidade'])->assertOk();
        $report = $this->report();
        $this->assertSame('review_required', $report['state']);
        $this->assertSame(0, $report['summary']['outgoing_cents']);
        $this->assertSame(1000, $report['closing']['totals']['outgoing_cents']);
        $this->assertSame('cancelled', $report['rows'][0]['status']);
        $event = DB::table('financial_audit_events')->where('action', 'payment.cancelled')->first();
        $this->assertNull(json_decode($event->before, true)['cancelled_at']);
        $this->assertNotNull(json_decode($event->after, true)['cancelled_at']);
        $this->postJson('/api/finance/closing/reopen', ['month' => '2028-01', 'note' => 'Revisar estorno posterior.'])->assertOk();
        $this->assertSame('open', $this->report()['state']);
        $this->assertDatabaseHas('financial_audit_events', ['action' => 'period.reopened']);
    }

    public function test_rejeita_previa_desatualizada_e_pagamento_cancelado(): void
    {
        $id = $this->payment();
        $row = $this->report()['rows'][0];
        $this->check($row);
        $report = $this->report();
        $this->payment('2028-01-11');
        $this->postJson('/api/finance/closing', ['month' => '2028-01', 'fingerprint' => $report['fingerprint'], 'note' => 'Fechar'])->assertConflict();
        $this->putJson('/api/finance/reconciliation/outgoing/'.$id, ['observed_amount_cents' => 1000, 'observed_on' => '2028-01-10', 'reference' => 'Extrato', 'note' => 'Conferido', 'source_hash' => str_repeat('0', 64)])->assertConflict();
        $this->postJson('/api/payables/'.$this->payable->id.'/payments/'.$id.'/cancel', ['reason' => 'Corrigir lançamento'])->assertOk();
        $this->putJson('/api/finance/reconciliation/outgoing/'.$id, ['observed_amount_cents' => 1000, 'observed_on' => '2028-01-10', 'reference' => 'Extrato', 'note' => 'Conferido', 'source_hash' => $row['source_hash']])->assertConflict();
    }

    public function test_concilia_recebimento_e_data_divergente_tambem_exige_revisao(): void
    {
        $folder = $this->organization->folders()->create(['name' => 'Pasta']);
        $client = $this->organization->clients()->create(['name' => 'Cliente', 'document' => '12345678901']);
        $invoice = Invoice::create(['organization_id' => $this->organization->id, 'number' => 'CONC-001', 'charge_identifier' => 'CONC-001', 'folder_id' => $folder->id, 'client_id' => $client->id, 'issued_on' => '2028-01-01', 'due_on' => '2028-01-10', 'subtotal_cents' => 1000, 'total_cents' => 1000, 'balance_cents' => 1000, 'status' => 'open']);
        $this->postJson('/api/invoices/'.$invoice->id.'/payments', ['amount_cents' => 1000, 'method' => 'pix', 'paid_at' => '2028-01-10'])->assertCreated();
        $row = $this->report()['rows'][0];
        $this->assertSame('incoming', $row['kind']);
        $this->check($row, ['observed_on' => '2028-01-11']);
        $this->assertSame('divergent', $this->report()['rows'][0]['status']);
        $this->check($row);
        $this->assertSame(1000, $this->report()['summary']['net_cents']);
    }

    public function test_valida_mes_motivo_e_nao_fecha_mes_em_andamento(): void
    {
        $this->getJson('/api/finance/reconciliation?month=2028-13')->assertUnprocessable();
        $this->postJson('/api/finance/closing/reopen', ['month' => '2028-01'])->assertUnprocessable();
        $report = $this->getJson('/api/finance/reconciliation?month=2028-02')->assertOk()->json();
        $this->postJson('/api/finance/closing', ['month' => '2028-02', 'fingerprint' => $report['fingerprint'], 'note' => 'Fechar'])->assertUnprocessable();
    }

    public function test_isolamento_e_permissao_de_consulta_sem_gestao(): void
    {
        $id = $this->payment();
        $other = Organization::where('slug', OrganizationSeeder::SECONDARY_SLUG)->firstOrFail();
        $foreignPayable = Payable::create(['organization_id' => $other->id, 'supplier' => 'Outro escritório', 'description' => 'Conta', 'due_on' => '2028-01-10', 'amount_cents' => 1000, 'balance_cents' => 0, 'status' => 'paid']);
        $foreign = PayablePayment::create(['organization_id' => $other->id, 'payable_id' => $foreignPayable->id, 'paid_at' => '2028-01-10', 'amount_cents' => 1000, 'method' => 'pix']);
        $this->assertCount(1, $this->report()['rows']);
        $this->putJson('/api/finance/reconciliation/outgoing/'.$foreign->id, ['observed_amount_cents' => 1000, 'observed_on' => '2028-01-10', 'reference' => 'Extrato', 'note' => 'Conferido', 'source_hash' => str_repeat('0', 64)])->assertNotFound();
        $user = User::factory()->create();
        $this->organization->users()->attach($user->id, ['status' => 'active']);
        setPermissionsTeamId($this->organization->id);
        $user->givePermissionTo('finance.view');
        $this->withToken(auth('api')->login($user));
        $this->getJson('/api/finance/reconciliation?month=2028-01')->assertOk();
        $this->putJson('/api/finance/reconciliation/outgoing/'.$id, [])->assertForbidden();
        $this->postJson('/api/finance/closing', [])->assertForbidden();
    }

    public function test_auditoria_rollback_nao_registra_operacao_financeira_desfeita(): void
    {
        $before = DB::table('financial_audit_events')->count();
        DB::beginTransaction();
        PayablePayment::create(['organization_id' => $this->organization->id, 'payable_id' => $this->payable->id, 'paid_at' => '2028-01-10', 'amount_cents' => 1000, 'method' => 'pix']);
        DB::rollBack();
        $this->assertSame($before, DB::table('financial_audit_events')->count());
    }
}
