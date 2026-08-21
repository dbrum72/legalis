<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PermissionSeeder::class
        );
    }

    public function test_usuario_pode_criar_conta_e_organizacao(): void
    {
        $response =
            $this->postJson(
                '/api/auth/register',
                $this->validPayload(),
            );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'organizations' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
            ]);

        $user =
            User::query()
            ->where(
                'email',
                'joao@silva.test',
            )
            ->firstOrFail();

        $organization =
            Organization::query()
            ->where(
                'name',
                'Silva Advocacia',
            )
            ->firstOrFail();

        $this->assertSame(
            'João Silva',
            $user->name,
        );

        $this->assertTrue(
            Hash::check(
                'Password123!',
                $user->password,
            )
        );

        $this->assertDatabaseHas(
            'organization_user',
            [
                'organization_id' =>
                $organization->id,

                'user_id' =>
                $user->id,

                'status' =>
                'active',
            ],
        );
    }

    public function test_nova_organizacao_recebe_rbac_completo(): void
    {
        $this->postJson(
            '/api/auth/register',
            $this->validPayload(),
        )->assertCreated();

        $organization =
            Organization::query()
            ->where(
                'name',
                'Silva Advocacia',
            )
            ->firstOrFail();

        $this->assertSame(
            10,
            Role::query()
                ->where(
                    'organization_id',
                    $organization->id,
                )
                ->where(
                    'guard_name',
                    'api',
                )
                ->count(),
        );

        $this->assertDatabaseHas(
            'roles',
            [
                'organization_id' =>
                $organization->id,

                'name' =>
                'socio-administrador',

                'guard_name' =>
                'api',
            ],
        );
    }

    public function test_criador_recebe_role_socio_administrador_na_nova_organizacao(): void
    {
        $this->postJson(
            '/api/auth/register',
            $this->validPayload(),
        )->assertCreated();

        $user =
            User::query()
            ->where(
                'email',
                'joao@silva.test',
            )
            ->firstOrFail();

        $organization =
            Organization::query()
            ->where(
                'name',
                'Silva Advocacia',
            )
            ->firstOrFail();

        setPermissionsTeamId(
            $organization->id
        );

        $user->unsetRelation('roles');

        $this->assertTrue(
            $user->hasRole(
                'socio-administrador'
            )
        );
    }

    public function test_email_deve_ser_unico(): void
    {
        User::factory()
            ->create([
                'email' =>
                'joao@silva.test',
            ]);

        $response =
            $this->postJson(
                '/api/auth/register',
                $this->validPayload(),
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);

        $this->assertDatabaseMissing(
            'organizations',
            [
                'name' =>
                'Silva Advocacia',
            ],
        );
    }

    public function test_confirmacao_da_senha_e_obrigatoria(): void
    {
        $payload =
            $this->validPayload();

        unset(
            $payload['password_confirmation']
        );

        $response =
            $this->postJson(
                '/api/auth/register',
                $payload,
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password',
            ]);

        $this->assertDatabaseMissing(
            'users',
            [
                'email' =>
                'joao@silva.test',
            ],
        );
    }

    public function test_nome_da_organizacao_e_obrigatorio(): void
    {
        $payload =
            $this->validPayload();

        unset(
            $payload['organization_name']
        );

        $response =
            $this->postJson(
                '/api/auth/register',
                $payload,
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'organization_name',
            ]);

        $this->assertDatabaseMissing(
            'users',
            [
                'email' =>
                'joao@silva.test',
            ],
        );
    }

    public function test_slug_da_organizacao_e_gerado_automaticamente(): void
    {
        $this->postJson(
            '/api/auth/register',
            $this->validPayload(),
        )->assertCreated();

        $this->assertDatabaseHas(
            'organizations',
            [
                'name' =>
                'Silva Advocacia',

                'slug' =>
                'silva-advocacia',
            ],
        );
    }

    public function test_slug_da_organizacao_permanece_unico(): void
    {
        Organization::factory()
            ->create([
                'name' =>
                'Silva Advocacia',

                'slug' =>
                'silva-advocacia',
            ]);

        $this->postJson(
            '/api/auth/register',
            $this->validPayload(),
        )->assertCreated();

        $organization =
            Organization::query()
            ->where(
                'name',
                'Silva Advocacia',
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertNotSame(
            'silva-advocacia',
            $organization->slug,
        );

        $this->assertStringStartsWith(
            'silva-advocacia-',
            $organization->slug,
        );
    }

    private function validPayload(): array
    {
        return [
            'name' =>
            'João Silva',

            'email' =>
            'joao@silva.test',

            'password' =>
            'Password123!',

            'password_confirmation' =>
            'Password123!',

            'organization_name' =>
            'Silva Advocacia',
        ];
    }
}
