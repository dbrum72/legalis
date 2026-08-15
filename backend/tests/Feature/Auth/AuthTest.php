<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            DatabaseSeeder::class
        );

        $this->organization =
            Organization::query()
            ->where(
                'slug',
                OrganizationSeeder::DEFAULT_SLUG,
            )
            ->firstOrFail();
    }

    public function test_login_rejeita_credenciais_invalidas(): void
    {
        $this
            ->postJson(
                '/api/auth/login',
                [
                    'email' =>
                    'super-admin@legalis.local',

                    'password' =>
                    'senha-incorreta',
                ],
            )
            ->assertForbidden()
            ->assertJson([
                'msg' =>
                'Usuário e/ou senha inválidos.',
            ]);
    }

    public function test_login_exige_email_e_senha(): void
    {
        $this
            ->postJson(
                '/api/auth/login',
                [],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
                'password',
            ]);
    }

    public function test_login_retorna_token_usuario_e_organizacoes(): void
    {
        $response =
            $this->postJson(
                '/api/auth/login',
                [
                    'email' =>
                    'super-admin@legalis.local',

                    'password' =>
                    'l3g@l1s',
                ],
            );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'access_token',
                'token_type',
                'expires_in',
                'userName',
                'userMail',
                'user',
                'organizations',
            ])
            ->assertJsonPath(
                'token_type',
                'bearer',
            )
            ->assertJsonPath(
                'user.email',
                'super-admin@legalis.local',
            )
            ->assertJsonPath(
                'organizations.0.slug',
                $this->organization->slug,
            );

        $response
            ->assertJsonMissingPath(
                'roles'
            )
            ->assertJsonMissingPath(
                'permissions'
            );
    }

    public function test_me_exige_autenticacao(): void
    {
        $this
            ->getJson(
                '/api/auth/me'
            )
            ->assertUnauthorized();
    }

    public function test_me_retorna_usuario_e_organizacoes_sem_rbac(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->withToken($token)
            ->getJson(
                '/api/auth/me'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'user.email',
                'super-admin@legalis.local',
            )
            ->assertJsonPath(
                'organizations.0.slug',
                $this->organization->slug,
            )
            ->assertJsonMissingPath(
                'roles'
            )
            ->assertJsonMissingPath(
                'permissions'
            );
    }

    public function test_context_exige_x_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->getJson(
                '/api/auth/context'
            )
            ->assertBadRequest();
    }

    public function test_context_retorna_roles_e_permissions_da_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $this
                    ->organization
                    ->slug,
            )
            ->getJson(
                '/api/auth/context'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'organization.id',
                $this->organization->id,
            );

        $this->assertContains(
            'super-admin',
            $response->json(
                'roles'
            ),
        );

        $this->assertContains(
            'clients.view',
            $response->json(
                'permissions'
            ),
        );

        $this->assertContains(
            'folders.delete',
            $response->json(
                'permissions'
            ),
        );
    }

    public function test_refresh_exige_autenticacao(): void
    {
        $this
            ->postJson(
                '/api/auth/refresh'
            )
            ->assertUnauthorized();
    }

    public function test_refresh_emite_novo_token_e_organizacoes(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->withToken($token)
            ->postJson(
                '/api/auth/refresh'
            );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'access_token',
                'token_type',
                'expires_in',
                'userName',
                'userMail',
                'user',
                'organizations',
            ])
            ->assertJsonMissingPath(
                'roles'
            )
            ->assertJsonMissingPath(
                'permissions'
            );

        $this->assertNotEmpty(
            $response->json(
                'access_token'
            )
        );
    }

    public function test_logout_exige_autenticacao(): void
    {
        $this
            ->postJson(
                '/api/auth/logout'
            )
            ->assertUnauthorized();
    }

    public function test_logout_invalida_token(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->postJson(
                '/api/auth/logout'
            )
            ->assertOk()
            ->assertJson([
                'msg' =>
                'Desconectado com sucesso.',
            ]);

        $this
            ->withToken($token)
            ->getJson(
                '/api/auth/me'
            )
            ->assertUnauthorized();
    }

    private function loginAsSuperAdmin(): string
    {
        $response =
            $this->postJson(
                '/api/auth/login',
                [
                    'email' =>
                    'super-admin@legalis.local',

                    'password' =>
                    'l3g@l1s',
                ],
            );

        $response->assertOk();

        return $response->json(
            'access_token'
        );
    }
}
