<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\MaritalStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_index_exige_autenticacao(): void
    {
        $this->getJson('/api/clients')
            ->assertUnauthorized();
    }

    public function test_usuario_sem_permissao_nao_pode_listar_clientes(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->getJson('/api/clients')
            ->assertForbidden();
    }

    public function test_usuario_com_permissao_pode_listar_clientes(): void
    {
        $user = User::where(
            'email',
            'super-admin@legalis.local'
        )->firstOrFail();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->getJson('/api/clients')
            ->assertOk();
    }

    public function test_cria_cliente(): void
    {
        $user = User::where(
            'email',
            'super-admin@legalis.local'
        )->firstOrFail();

        $token = auth('api')->login($user);

        $maritalStatus = MaritalStatus::firstOrFail();

        $payload = [
            'name' => 'Cliente Teste',
            'document' => '12345678901',
            'identity_document' => '123456789',
            'identity_issuer' => 'SSP',
            'marital_status_id' => $maritalStatus->id,
            'profession' => 'Advogado',
            'address' => 'Rua Teste, 100',
            'address_complement' => 'Sala 2',
            'district' => 'Centro',
            'city' => 'Pelotas',
            'postal_code' => '96000000',
            'phone' => '53999999999',
            'whatsapp' => true,
            'email' => 'cliente@teste.com',
        ];

        $response = $this
            ->withToken($token)
            ->postJson('/api/clients', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Cliente Teste')
            ->assertJsonPath('document', '12345678901')
            ->assertJsonPath(
                'marital_status.id',
                $maritalStatus->id
            );

        $this->assertDatabaseHas('clients', [
            'document' => '12345678901',
        ]);
    }

    public function test_documento_deve_ser_unico(): void
    {
        Client::create([
            'name' => 'Cliente Existente',
            'document' => '12345678901',
        ]);

        $user = User::where(
            'email',
            'super-admin@legalis.local'
        )->firstOrFail();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->postJson('/api/clients', [
                'name' => 'Outro Cliente',
                'document' => '12345678901',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'document',
            ]);
    }

    public function test_marital_status_deve_existir(): void
    {
        $user = User::where(
            'email',
            'super-admin@legalis.local'
        )->firstOrFail();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->postJson('/api/clients', [
                'name' => 'Cliente Teste',
                'document' => '12345678901',
                'marital_status_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'marital_status_id',
            ]);
    }

    public function test_exibe_cliente_com_estado_civil(): void
    {
        $maritalStatus = MaritalStatus::firstOrFail();

        $client = Client::create([
            'name' => 'Cliente Teste',
            'document' => '12345678901',
            'marital_status_id' => $maritalStatus->id,
        ]);

        $user = User::where(
            'email',
            'super-admin@legalis.local'
        )->firstOrFail();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath(
                'marital_status.id',
                $maritalStatus->id
            );
    }

    public function test_atualiza_cliente(): void
    {
        $client = Client::create([
            'name' => 'Cliente Antigo',
            'document' => '12345678901',
        ]);

        $user = User::where(
            'email',
            'super-admin@legalis.local'
        )->firstOrFail();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->patchJson(
                "/api/clients/{$client->id}",
                [
                    'name' => 'Cliente Atualizado',
                    'document' => '12345678901',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'name',
                'Cliente Atualizado'
            );

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Cliente Atualizado',
        ]);
    }

    public function test_exclui_cliente(): void
    {
        $client = Client::create([
            'name' => 'Cliente Excluir',
            'document' => '12345678901',
        ]);

        $user = User::where(
            'email',
            'super-admin@legalis.local'
        )->firstOrFail();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->deleteJson(
                "/api/clients/{$client->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing('clients', [
            'id' => $client->id,
        ]);
    }
}
