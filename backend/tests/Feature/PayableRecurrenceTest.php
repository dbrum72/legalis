<?php

namespace Tests\Feature;

use App\Models\FinancialClassification;
use App\Models\Organization;
use App\Models\Payable;
use App\Models\PayableRecurrence;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayableRecurrenceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2028-01-15 12:00:00'));
        $this->seed(DatabaseSeeder::class);
        $this->organization = Organization::where('slug', OrganizationSeeder::DEFAULT_SLUG)->firstOrFail();
        $user = User::where('email', 'super-admin@legalis.local')->firstOrFail();
        $this->withToken(auth('api')->login($user))->withHeader('X-Tenant', $this->organization->slug);
    }

    private function payload(array $extra = []): array
    {
        return $extra + ['supplier' => 'Locador', 'description' => 'Aluguel', 'amount_cents' => 12345, 'due_on' => '2028-01-31', 'interval_months' => 1, 'ends_on' => null];
    }

    public function test_gera_com_fim_de_mes_ano_bissexto_e_sem_duplicar(): void
    {
        $id = $this->postJson('/api/payable-recurrences', $this->payload())->assertCreated()->assertJsonPath('created', 1)->json('recurrence.id');
        $this->getJson("/api/payable-recurrences/{$id}/preview?through=2028-04-01")->assertOk()->assertJsonPath('dates', ['2028-02-29', '2028-03-31']);
        $this->postJson("/api/payable-recurrences/{$id}/generate", ['through' => '2028-04-01'])->assertOk()->assertJsonPath('created', 2);
        $this->postJson("/api/payable-recurrences/{$id}/generate", ['through' => '2028-04-01'])->assertOk()->assertJsonPath('created', 0);
        $this->assertDatabaseCount('payables', 3);
        $this->assertDatabaseCount('payable_recurrence_occurrences', 3);
        $this->getJson('/api/payables')->assertOk()->assertJsonPath('0.recurrence_id', $id);
    }

    public function test_pausa_retoma_e_respeita_termino_inclusivo(): void
    {
        $id = $this->postJson('/api/payable-recurrences', $this->payload(['ends_on' => '2028-02-29']))->assertCreated()->json('recurrence.id');
        $this->patchJson("/api/payable-recurrences/{$id}", ['active' => false])->assertOk();
        $this->postJson("/api/payable-recurrences/{$id}/generate", ['through' => '2028-04-01'])->assertOk()->assertJsonPath('created', 0);
        $this->patchJson("/api/payable-recurrences/{$id}", ['active' => true])->assertOk();
        $this->postJson("/api/payable-recurrences/{$id}/generate", ['through' => '2028-04-01'])->assertOk()->assertJsonPath('created', 1)->assertJsonPath('recurrence.next_due_on', null);
    }

    public function test_edicao_afeta_so_contas_nao_geradas_e_cancelamento_nao_recria(): void
    {
        $id = $this->postJson('/api/payable-recurrences', $this->payload())->assertCreated()->json('recurrence.id');
        $payable = Payable::firstOrFail();
        $this->patchJson("/api/payable-recurrences/{$id}", ['amount_cents' => 22222, 'description' => 'Novo aluguel'])->assertOk();
        $this->assertSame(12345, $payable->fresh()->amount_cents);
        $this->postJson("/api/payables/{$payable->id}/cancel", ['reason' => 'Sem obrigação neste mês'])->assertOk();
        $this->deleteJson("/api/payables/{$payable->id}")->assertUnprocessable();
        $this->postJson("/api/payable-recurrences/{$id}/generate", ['through' => '2028-03-01'])->assertOk()->assertJsonPath('created', 1);
        $this->assertDatabaseHas('payables', ['due_on' => '2028-02-29', 'amount_cents' => 22222, 'description' => 'Novo aluguel']);
        $this->assertDatabaseCount('payables', 2);
    }

    public function test_rejeita_calendario_valor_e_horizonte_invalidos(): void
    {
        foreach ([['interval_months' => 2], ['due_on' => '2028-01-01'], ['ends_on' => '2028-01-20'], ['amount_cents' => 0], ['due_on' => '2028-02-30']] as $extra) {
            $this->postJson('/api/payable-recurrences', $this->payload($extra))->assertUnprocessable();
        }
        $id = $this->postJson('/api/payable-recurrences', $this->payload())->assertCreated()->json('recurrence.id');
        $this->postJson("/api/payable-recurrences/{$id}/generate", ['through' => '2030-01-01'])->assertUnprocessable();
        $this->assertDatabaseCount('payables', 1);
    }

    public function test_isola_escritorios_e_bloqueia_gestao_sem_permissao(): void
    {
        $id = $this->postJson('/api/payable-recurrences', $this->payload())->assertCreated()->json('recurrence.id');
        $other = Organization::where('slug', OrganizationSeeder::SECONDARY_SLUG)->firstOrFail();
        $admin = User::where('email', 'super-admin@legalis.local')->firstOrFail();
        $other->users()->syncWithoutDetaching([$admin->id => ['status' => 'active']]);
        setPermissionsTeamId($other->id);
        $admin->unsetRelation('roles')->unsetRelation('permissions');
        $admin->assignRole('super-admin');
        $this->withHeader('X-Tenant', $other->slug);
        $this->getJson('/api/payable-recurrences')->assertOk()->assertJsonCount(0);
        $this->patchJson("/api/payable-recurrences/{$id}", ['active' => false])->assertNotFound();
        $this->postJson("/api/payable-recurrences/{$id}/generate", ['through' => '2028-03-01'])->assertNotFound();
        $user = User::factory()->create();
        $this->organization->users()->attach($user->id, ['status' => 'active']);
        $this->withHeader('X-Tenant', $this->organization->slug)->withToken(auth('api')->login($user));
        $this->getJson('/api/payable-recurrences')->assertForbidden();
        $this->postJson('/api/payable-recurrences', $this->payload())->assertForbidden();
        $this->getJson('/api/payables/alerts')->assertForbidden();
    }

    public function test_alerta_apenas_saldo_em_aberto_dos_proximos_sete_dias(): void
    {
        foreach ([['2028-01-15', 'open', 100], ['2028-01-22', 'partial', 50], ['2028-01-23', 'open', 100], ['2028-01-14', 'open', 100], ['2028-01-16', 'paid', 0], ['2028-01-16', 'cancelled', 0]] as [$date, $status, $balance]) {
            Payable::create(['organization_id' => $this->organization->id, 'supplier' => 'Fornecedor', 'description' => 'Conta', 'amount_cents' => 100, 'balance_cents' => $balance, 'status' => $status, 'due_on' => $date]);
        }
        $this->getJson('/api/payables/alerts')->assertOk()->assertJsonPath('due_today', 1)->assertJsonPath('due_soon', 2);
        $this->getJson('/api/payables?status=due_soon')->assertOk()->assertJsonCount(2);
    }

    public function test_rotina_diaria_idempotente_restaura_contexto_e_registra_falha_sem_perder_cursor(): void
    {
        $category = FinancialClassification::create(['organization_id' => $this->organization->id, 'kind' => 'category', 'name' => 'Aluguel', 'active' => true]);
        $id = $this->postJson('/api/payable-recurrences', $this->payload(['category_id' => $category->id]))->assertCreated()->json('recurrence.id');
        $this->travelTo(CarbonImmutable::parse('2028-02-01 12:00:00'));
        app(CurrentOrganization::class)->set($this->organization);
        $category->update(['active' => false]);
        $this->artisan('finance:generate-payables')->assertFailed();
        $this->assertNotNull(PayableRecurrence::findOrFail($id)->last_error);
        $this->assertSame('2028-02-29', PayableRecurrence::findOrFail($id)->next_due_on->toDateString());
        $category->update(['active' => true]);
        $this->artisan('finance:generate-payables')->assertSuccessful();
        $this->artisan('finance:generate-payables')->assertSuccessful();
        $this->assertDatabaseCount('payables', 2);
        $this->assertNull(PayableRecurrence::findOrFail($id)->last_error);
        $this->assertSame($this->organization->id, app(CurrentOrganization::class)->id());
    }
}
