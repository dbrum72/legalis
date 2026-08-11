<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_rejeita_credenciais_invalidas(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'super-admin@legalis.local',
            'password' => 'senha-incorreta',
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'msg' => 'Usuário e/ou senha inválidos.',
            ]);
    }

    public function test_login_exige_email_e_senha(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
                'password',
            ]);
    }

    public function test_login_retorna_token_usuario_roles_e_permissions(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'super-admin@legalis.local',
            'password' => 'l3g@l1s',
        ]);

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
                'roles',
                'permissions',
            ])
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath(
                'user.email',
                'super-admin@legalis.local'
            );

        $this->assertContains(
            'super-admin',
            $response->json('roles')
        );

        $this->assertCount(
            19,
            $response->json('permissions')
        );
    }

    public function test_me_exige_autenticacao(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_me_retorna_usuario_autenticado(): void
    {
        $token = $this->loginAsSuperAdmin();

        $response = $this
            ->withToken($token)
            ->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath(
                'email',
                'super-admin@legalis.local'
            );
    }

    public function test_refresh_exige_autenticacao(): void
    {
        $this->postJson('/api/auth/refresh')
            ->assertUnauthorized();
    }

    public function test_refresh_emite_novo_token(): void
    {
        $token = $this->loginAsSuperAdmin();

        $response = $this
            ->withToken($token)
            ->postJson('/api/auth/refresh');

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
                'roles',
                'permissions',
            ]);

        $this->assertNotEmpty(
            $response->json('access_token')
        );
    }

    public function test_logout_exige_autenticacao(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertUnauthorized();
    }

    public function test_logout_invalida_token(): void
    {
        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJson([
                'msg' => 'Desconectado com sucesso.',
            ]);

        $this
            ->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    private function loginAsSuperAdmin(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'super-admin@legalis.local',
            'password' => 'l3g@l1s',
        ]);

        $response->assertOk();

        return $response->json('access_token');
    }
}