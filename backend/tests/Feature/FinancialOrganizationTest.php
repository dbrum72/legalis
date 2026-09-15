<?php

namespace Tests\Feature;

use App\Models\FinancialClassification;
use App\Models\Organization;
use App\Models\Qualification;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialOrganizationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->organization = Organization::where('slug', OrganizationSeeder::DEFAULT_SLUG)->firstOrFail();
        $user = User::where('email', 'super-admin@legalis.local')->firstOrFail();
        $this->withToken(auth('api')->login($user))->withHeader('X-Tenant', $this->organization->slug);
    }

    public function test_cadastra_renomeia_desativa_e_rejeita_duplicidade(): void
    {
        $category = $this->postJson('/api/finance/classifications', ['kind' => 'category', 'name' => '  Estrutura  '])->assertCreated()->assertJsonPath('name', 'Estrutura')->json('id');
        $this->postJson('/api/finance/classifications', ['kind' => 'category', 'name' => 'Estrutura'])->assertUnprocessable();
        $this->postJson('/api/finance/classifications', ['kind' => 'cost_center', 'name' => 'Estrutura'])->assertCreated();
        $this->patchJson('/api/finance/classifications/'.$category, ['name' => 'Administrativo', 'active' => false])->assertOk()->assertJsonPath('active', false);
        $this->getJson('/api/finance/classifications')->assertOk()->assertJsonCount(2);
        $this->postJson('/api/finance/classifications', ['kind' => 'invalid', 'name' => 'Teste'])->assertUnprocessable();
    }

    public function test_conta_classificada_pode_ser_filtrada_e_desativacao_preserva_vinculo(): void
    {
        $category = $this->classification('category');
        $center = $this->classification('cost_center');
        $payload = $this->payable(['category_id' => $category->id, 'cost_center_id' => $center->id]);
        $id = $this->postJson('/api/payables', $payload)->assertCreated()->json('id');
        $this->postJson('/api/payables', $this->payable())->assertCreated();
        $this->getJson('/api/payables?category_id='.$category->id.'&cost_center_id='.$center->id)->assertOk()->assertJsonCount(1)->assertJsonPath('0.financial_category.name', 'Categoria')->assertJsonPath('0.cost_center.name', 'Centro');
        $this->patchJson('/api/finance/classifications/'.$category->id, ['active' => false])->assertOk();
        $this->postJson('/api/payables', $payload)->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->patchJson('/api/payables/'.$id, $payload)->assertOk()->assertJsonPath('category_id', $category->id);
        $this->patchJson('/api/payables/'.$id, $this->payable(['category_id' => null]))->assertOk()->assertJsonPath('category_id', null);
    }

    public function test_rejeita_tipo_incorreto_e_classificacao_de_outro_escritorio(): void
    {
        $center = $this->classification('cost_center');
        app(CurrentOrganization::class)->clear();
        $other = Organization::where('slug', OrganizationSeeder::SECONDARY_SLUG)->firstOrFail();
        $foreign = FinancialClassification::create(['organization_id' => $other->id, 'kind' => 'category', 'name' => 'Sigilosa', 'active' => true]);
        $this->postJson('/api/payables', $this->payable(['category_id' => $center->id]))->assertUnprocessable();
        $this->postJson('/api/payables', $this->payable(['category_id' => $foreign->id]))->assertUnprocessable();
        $this->patchJson('/api/finance/classifications/'.$foreign->id, ['name' => 'Invadida'])->assertNotFound();
        $this->getJson('/api/finance/classifications')->assertOk()->assertJsonCount(1);
    }

    public function test_vinculos_opcionais_exigem_mesmo_escritorio_e_cliente_da_pasta(): void
    {
        $folder = $this->organization->folders()->create(['name' => 'Pasta']);
        $client = $this->organization->clients()->create(['name' => 'Cliente', 'document' => '12345678901']);
        $this->postJson('/api/payables', $this->payable(['folder_id' => $folder->id]))->assertCreated();
        $this->postJson('/api/payables', $this->payable(['client_id' => $client->id]))->assertCreated();
        $this->postJson('/api/payables', $this->payable(['folder_id' => $folder->id, 'client_id' => $client->id]))->assertUnprocessable();
        $folder->clients()->attach($client->id, ['qualification_id' => Qualification::value('id')]);
        $this->postJson('/api/payables', $this->payable(['folder_id' => $folder->id, 'client_id' => $client->id]))->assertCreated();
        app(CurrentOrganization::class)->clear();
        $other = Organization::where('slug', OrganizationSeeder::SECONDARY_SLUG)->firstOrFail();
        $foreignFolder = $other->folders()->create(['name' => 'Pasta sigilosa']);
        $this->postJson('/api/payables', $this->payable(['folder_id' => $foreignFolder->id]))->assertUnprocessable();
    }

    public function test_despesas_compartilham_catalogo_e_nao_reembolsavel_nao_pode_ser_faturada(): void
    {
        $category = $this->classification('category');
        $folder = $this->organization->folders()->create(['name' => 'Pasta']);
        $client = $this->organization->clients()->create(['name' => 'Cliente', 'document' => '12345678901']);
        $folder->clients()->attach($client->id, ['qualification_id' => Qualification::value('id')]);
        $expense = $this->postJson('/api/folders/'.$folder->id.'/expenses', ['incurred_on' => '2026-09-14', 'description' => 'Custo interno', 'amount_cents' => 10000, 'reimbursable' => false, 'category_id' => $category->id])->assertCreated()->assertJsonPath('financial_category.name', 'Categoria')->json('id');
        $this->postJson('/api/folders/'.$folder->id.'/billing', ['client_id' => $client->id, 'due_on' => '2026-09-30', 'expense_ids' => [$expense]])->assertUnprocessable();
        $this->getJson('/api/folders/'.$folder->id.'/expenses')->assertOk()->assertJsonPath('0.category_id', $category->id);
        $this->assertDatabaseCount('payables', 0);
    }

    public function test_membro_sem_permissao_nao_acessa_catalogo(): void
    {
        $user = User::factory()->create();
        $this->organization->users()->attach($user->id, ['status' => 'active']);
        $this->withToken(auth('api')->login($user));
        $this->getJson('/api/finance/classifications')->assertForbidden();
        $this->postJson('/api/finance/classifications', ['kind' => 'category', 'name' => 'Teste'])->assertForbidden();
    }

    private function classification(string $kind): FinancialClassification
    {
        return FinancialClassification::create(['organization_id' => $this->organization->id, 'kind' => $kind, 'name' => $kind === 'category' ? 'Categoria' : 'Centro', 'active' => true]);
    }

    private function payable(array $extra = []): array
    {
        return $extra + ['supplier' => 'Fornecedor', 'description' => 'Serviço', 'amount_cents' => 10000, 'due_on' => '2026-09-30'];
    }
}
