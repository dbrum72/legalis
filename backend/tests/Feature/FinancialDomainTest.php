<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\FeeAgreement;
use App\Models\Folder;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\Organizations\OrganizationRoleDefinitions;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_fluxo_financeiro_completo_vinculado_a_pasta(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $client = Client::factory()->for($organization)->create();
        $folder = Folder::factory()->for($organization)->create();

        $this->asOrganization($organization, function () use ($client, $folder, $organization, $user): void {
            $agreement = FeeAgreement::query()->create([
                'folder_id' => $folder->id,
                'client_id' => $client->id,
                'type' => 'hybrid',
                'status' => 'active',
                'hourly_rate_cents' => 35000,
                'fixed_fee_cents' => 200000,
                'contingency_percentage' => 15,
                'billing_day' => 10,
            ]);

            $invoice = Invoice::query()->create([
                'folder_id' => $folder->id,
                'client_id' => $client->id,
                'fee_agreement_id' => $agreement->id,
                'number' => '2026-0001',
                'charge_identifier' => 'COB-2026-0001',
                'issued_on' => '2026-09-08',
                'due_on' => '2026-09-15',
                'status' => 'open',
                'subtotal_cents' => 270000,
                'discount_cents' => 20000,
                'total_cents' => 250000,
                'paid_cents' => 100000,
                'balance_cents' => 150000,
            ]);

            $timeEntry = TimeEntry::query()->create([
                'folder_id' => $folder->id,
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'worked_on' => '2026-09-08',
                'duration_minutes' => 120,
                'description' => 'Elaboração de contestação',
                'hourly_rate_cents' => 35000,
                'billable' => true,
                'status' => 'billed',
            ]);

            $expense = Expense::query()->create([
                'folder_id' => $folder->id,
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'incurred_on' => '2026-09-08',
                'description' => 'Custas processuais',
                'amount_cents' => 18000,
                'reimbursable' => true,
                'status' => 'billed',
            ]);

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'recorded_by' => $user->id,
                'paid_at' => '2026-09-08 14:30:00',
                'amount_cents' => 100000,
                'method' => 'pix',
                'reference' => 'PIX-001',
            ]);

            $this->assertSame($organization->id, $agreement->organization_id);
            $this->assertSame($organization->id, $invoice->organization_id);
            $this->assertSame($organization->id, $timeEntry->organization_id);
            $this->assertSame($organization->id, $expense->organization_id);
            $this->assertSame($organization->id, $payment->organization_id);
            $this->assertSame(70000, $timeEntry->billableAmountCents());
            $this->assertSame(18000, $expense->amount_cents);
            $this->assertTrue($expense->reimbursable);
            $this->assertCount(1, $invoice->payments);
            $this->assertTrue($payment->recordedBy->is($user));
        });
    }

    public function test_apontamento_nao_faturavel_tem_valor_faturavel_zero(): void
    {
        $entry = new TimeEntry([
            'duration_minutes' => 90,
            'hourly_rate_cents' => 30000,
            'billable' => false,
        ]);

        $this->assertSame(0, $entry->billableAmountCents());
    }

    public function test_consultas_financeiras_sao_isoladas_por_organizacao(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();

        foreach ([$organizationA, $organizationB] as $index => $organization) {
            $client = Client::factory()->for($organization)->create();
            $folder = Folder::factory()->for($organization)->create();

            $this->asOrganization($organization, function () use ($client, $folder, $index): void {
                Invoice::query()->create([
                    'folder_id' => $folder->id,
                    'client_id' => $client->id,
                    'number' => '2026-000'.($index + 1),
                    'charge_identifier' => 'COB-2026-000'.($index + 1),
                    'status' => 'draft',
                ]);
            });
        }

        $this->asOrganization($organizationA, function (): void {
            $this->assertSame(['2026-0001'], Invoice::query()->pluck('number')->all());
        });

        $this->asOrganization($organizationB, function (): void {
            $this->assertSame(['2026-0002'], Invoice::query()->pluck('number')->all());
        });
    }

    public function test_permissoes_financeiras_respeitam_as_responsabilidades_dos_perfis(): void
    {
        $definitions = OrganizationRoleDefinitions::definitions();

        $this->assertContains('finance.manage', $definitions['super-admin']['permissions']);
        $this->assertContains('finance.manage', $definitions['socio-administrador']['permissions']);
        $this->assertContains('finance.view', $definitions['socio']['permissions']);
        $this->assertNotContains('finance.manage', $definitions['socio']['permissions']);
        $this->assertNotContains('finance.view', $definitions['advogado-senior']['permissions']);
        $this->assertContains('time-entries.delete', $definitions['advogado-senior']['permissions']);
        $this->assertContains('expenses.delete', $definitions['advogado-senior']['permissions']);
        $this->assertContains('time-entries.create', $definitions['advogado-pleno']['permissions']);
        $this->assertContains('expenses.update', $definitions['assistente-juridico']['permissions']);
        $this->assertContains('time-entries.update', $definitions['estagiario-direito']['permissions']);
        $this->assertNotContains('expenses.delete', $definitions['estagiario-direito']['permissions']);
        $this->assertNotContains('finance.view', $definitions['paralegal']['permissions']);
    }

    private function asOrganization(Organization $organization, callable $callback): mixed
    {
        $currentOrganization = app(CurrentOrganization::class);
        $currentOrganization->set($organization);

        try {
            return $callback();
        } finally {
            $currentOrganization->clear();
        }
    }
}
