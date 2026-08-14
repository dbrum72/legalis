<?php

namespace Tests\Feature\Tenancy;

use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CurrentOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'auth:api',
            'tenant',
        ])
            ->get(
                '/api/testing/current-organization',
                function (
                    CurrentOrganization $currentOrganization
                ) {
                    return response()->json([
                        'id' =>
                        $currentOrganization->id(),

                        'slug' =>
                        $currentOrganization
                            ->get()
                            ->slug,
                    ]);
                }
            );
    }

    public function test_rejeita_requisicao_sem_x_tenant(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login(
            $user
        );

        $this
            ->withToken($token)
            ->getJson(
                '/api/testing/current-organization'
            )
            ->assertBadRequest()
            ->assertJson([
                'message' =>
                'O cabeçalho X-Tenant é obrigatório.',
            ]);
    }

    public function test_rejeita_organizacao_inexistente(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login(
            $user
        );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                'organizacao-inexistente',
            )
            ->getJson(
                '/api/testing/current-organization'
            )
            ->assertNotFound()
            ->assertJson([
                'message' =>
                'Organização não encontrada.',
            ]);
    }

    public function test_rejeita_organizacao_inativa(): void
    {
        $user = User::factory()->create();

        $organization =
            Organization::factory()
            ->inactive()
            ->create();

        $organization
            ->users()
            ->attach(
                $user->id,
                [
                    'status' => 'active',
                ],
            );

        $token = auth('api')->login(
            $user
        );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            )
            ->getJson(
                '/api/testing/current-organization'
            )
            ->assertNotFound()
            ->assertJson([
                'message' =>
                'Organização não encontrada.',
            ]);
    }

    public function test_rejeita_usuario_sem_membership(): void
    {
        $user = User::factory()->create();

        $organization =
            Organization::factory()->create();

        $token = auth('api')->login(
            $user
        );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            )
            ->getJson(
                '/api/testing/current-organization'
            )
            ->assertForbidden()
            ->assertJson([
                'message' =>
                'Usuário não possui acesso à organização informada.',
            ]);
    }

    public function test_rejeita_membership_inativa(): void
    {
        $user = User::factory()->create();

        $organization =
            Organization::factory()->create();

        $organization
            ->users()
            ->attach(
                $user->id,
                [
                    'status' =>
                    'inactive',
                ],
            );

        $token = auth('api')->login(
            $user
        );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            )
            ->getJson(
                '/api/testing/current-organization'
            )
            ->assertForbidden()
            ->assertJson([
                'message' =>
                'Usuário não possui acesso à organização informada.',
            ]);
    }

    public function test_resolve_organizacao_para_membership_ativa(): void
    {
        $user = User::factory()->create();

        $organization =
            Organization::factory()->create();

        $organization
            ->users()
            ->attach(
                $user->id,
                [
                    'status' =>
                    'active',
                ],
            );

        $token = auth('api')->login(
            $user
        );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            )
            ->getJson(
                '/api/testing/current-organization'
            )
            ->assertOk()
            ->assertJson([
                'id' =>
                $organization->id,

                'slug' =>
                $organization->slug,
            ]);
    }

    public function test_contexto_e_limpo_apos_requisicao(): void
    {
        $user = User::factory()->create();

        $organization =
            Organization::factory()->create();

        $organization
            ->users()
            ->attach(
                $user->id,
                [
                    'status' =>
                    'active',
                ],
            );

        $token = auth('api')->login(
            $user
        );

        $currentOrganization =
            app(
                CurrentOrganization::class
            );

        $this->assertFalse(
            $currentOrganization->has()
        );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            )
            ->getJson(
                '/api/testing/current-organization'
            )
            ->assertOk();

        $this->assertFalse(
            $currentOrganization->has()
        );
    }

    public function test_requisicao_sem_autenticacao_continua_sendo_401(): void
    {
        $organization =
            Organization::factory()->create();

        $this
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            )
            ->getJson(
                '/api/testing/current-organization'
            )
            ->assertUnauthorized();
    }
}
