<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualificationTest extends TestCase
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
            ->getJson('/api/qualifications')
            ->assertUnauthorized();
    }

    public function test_usuario_autenticado_pode_listar_qualificacoes(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->getJson('/api/qualifications')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                ],
            ]);
    }

    public function test_retorna_catalogo_sem_campos_desnecessarios(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/qualifications')
            ->assertOk();

        foreach ($response->json() as $qualification) {
            $this->assertSame(
                ['id', 'name'],
                array_keys($qualification)
            );
        }
    }

    public function test_catalogo_contem_qualificacoes_basicas(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/qualifications')
            ->assertOk();

        $names = collect($response->json())
            ->pluck('name');

        $this->assertTrue(
            $names->contains('Autor')
        );

        $this->assertTrue(
            $names->contains('Réu')
        );

        $this->assertTrue(
            $names->contains('Requerente')
        );

        $this->assertTrue(
            $names->contains('Requerido')
        );
    }
}