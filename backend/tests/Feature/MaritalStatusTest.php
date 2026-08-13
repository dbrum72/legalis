<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaritalStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_index_exige_autenticacao(): void
    {
        $this
            ->getJson('/api/marital-statuses')
            ->assertUnauthorized();
    }

    public function test_usuario_autenticado_pode_listar_estados_civis(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->getJson('/api/marital-statuses')
            ->assertOk()
            ->assertJsonCount(5)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                ],
            ]);
    }

    public function test_retorna_catalogo_na_ordem_definida(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/marital-statuses');

        $response
            ->assertOk()
            ->assertJsonPath('0.name', 'solteiro(a)')
            ->assertJsonPath('1.name', 'casado(a)')
            ->assertJsonPath('2.name', 'divorciado(a)')
            ->assertJsonPath('3.name', 'união estável')
            ->assertJsonPath('4.name', 'viúvo(a)');
    }

    public function test_retorna_apenas_id_e_name(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/marital-statuses')
            ->assertOk();

        foreach ($response->json() as $maritalStatus) {
            $this->assertSame(
                ['id', 'name'],
                array_keys($maritalStatus)
            );
        }
    }
}