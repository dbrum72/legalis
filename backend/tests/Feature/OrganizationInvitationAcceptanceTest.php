<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationInvitationAcceptanceTest extends TestCase
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
                OrganizationSeeder::DEFAULT_SLUG
            )
            ->firstOrFail();
    }

    public function test_exibe_convite_valido_para_usuario_novo(): void
    {
        [$token] =
            $this->createInvitation(
                'novo.usuario@example.com'
            );

        $this
            ->getJson(
                "/api/organization-invitations/accept/{$token}"
            )
            ->assertOk()
            ->assertJsonPath(
                'email',
                'novo.usuario@example.com'
            )
            ->assertJsonPath(
                'role',
                'advogado-junior'
            )
            ->assertJsonPath(
                'registration_required',
                true
            )
            ->assertJsonPath(
                'organization.id',
                $this->organization->id
            );
    }

    public function test_exibe_convite_valido_para_usuario_existente(): void
    {
        User::factory()
            ->create([
                'email' =>
                'existente@example.com',
            ]);

        [$token] =
            $this->createInvitation(
                'existente@example.com'
            );

        $this
            ->getJson(
                "/api/organization-invitations/accept/{$token}"
            )
            ->assertOk()
            ->assertJsonPath(
                'registration_required',
                false
            );
    }

    public function test_token_inexistente_retorna_404(): void
    {
        $this
            ->getJson(
                '/api/organization-invitations/accept/token-inexistente'
            )
            ->assertNotFound();
    }

    public function test_convite_expirado_retorna_410(): void
    {
        [$token] =
            $this->createInvitation(
                'expirado@example.com',
                [
                    'expires_at' =>
                    now()->subMinute(),
                ]
            );

        $this
            ->getJson(
                "/api/organization-invitations/accept/{$token}"
            )
            ->assertStatus(410);
    }

    public function test_convite_aceito_retorna_410(): void
    {
        [$token] =
            $this->createInvitation(
                'aceito@example.com',
                [
                    'status' =>
                    OrganizationInvitation::STATUS_ACCEPTED,

                    'accepted_at' =>
                    now(),
                ]
            );

        $this
            ->getJson(
                "/api/organization-invitations/accept/{$token}"
            )
            ->assertStatus(410);
    }

    public function test_convite_revogado_retorna_410(): void
    {
        [$token] =
            $this->createInvitation(
                'revogado@example.com',
                [
                    'status' =>
                    OrganizationInvitation::STATUS_REVOKED,

                    'revoked_at' =>
                    now(),
                ]
            );

        $this
            ->getJson(
                "/api/organization-invitations/accept/{$token}"
            )
            ->assertStatus(410);
    }

    public function test_usuario_novo_exige_nome(): void
    {
        [$token] =
            $this->createInvitation(
                'novo@example.com'
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'password' =>
                    'password123',

                    'password_confirmation' =>
                    'password123',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    public function test_usuario_novo_exige_senha(): void
    {
        [$token] =
            $this->createInvitation(
                'novo@example.com'
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'name' =>
                    'Novo Usuário',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password',
            ]);
    }

    public function test_usuario_novo_exige_confirmacao_da_senha(): void
    {
        [$token] =
            $this->createInvitation(
                'novo@example.com'
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'name' =>
                    'Novo Usuário',

                    'password' =>
                    'password123',

                    'password_confirmation' =>
                    'diferente',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password',
            ]);
    }

    public function test_aceite_cria_usuario_novo(): void
    {
        [$token] =
            $this->createInvitation(
                'novo.usuario@example.com'
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'name' =>
                    'Novo Usuário',

                    'password' =>
                    'password123',

                    'password_confirmation' =>
                    'password123',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'user.name',
                'Novo Usuário'
            )
            ->assertJsonPath(
                'user.email',
                'novo.usuario@example.com'
            )
            ->assertJsonPath(
                'organization.id',
                $this->organization->id
            )
            ->assertJsonPath(
                'role',
                'advogado-junior'
            );

        $this->assertDatabaseHas(
            'users',
            [
                'email' =>
                'novo.usuario@example.com',
            ]
        );
    }

    public function test_aceite_cria_membership_ativo(): void
    {
        [$token] =
            $this->createInvitation(
                'membership@example.com'
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'name' =>
                    'Membership Test',

                    'password' =>
                    'password123',

                    'password_confirmation' =>
                    'password123',
                ]
            )
            ->assertOk();

        $user =
            User::query()
            ->where(
                'email',
                'membership@example.com'
            )
            ->firstOrFail();

        $this->assertDatabaseHas(
            'organization_user',
            [
                'organization_id' =>
                $this->organization->id,

                'user_id' =>
                $user->id,

                'status' =>
                'active',
            ]
        );

        $membership =
            $this->organization
            ->users()
            ->whereKey(
                $user->id
            )
            ->firstOrFail()
            ->membership;

        $this->assertNotNull(
            $membership->joined_at
        );
    }

    public function test_aceite_atribui_role_no_tenant(): void
    {
        [$token] =
            $this->createInvitation(
                'role@example.com'
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'name' =>
                    'Role Test',

                    'password' =>
                    'password123',

                    'password_confirmation' =>
                    'password123',
                ]
            )
            ->assertOk();

        $user =
            User::query()
            ->where(
                'email',
                'role@example.com'
            )
            ->firstOrFail();

        $previousTeamId =
            getPermissionsTeamId();

        try {
            setPermissionsTeamId(
                $this->organization->id
            );

            $user
                ->unsetRelation('roles')
                ->unsetRelation(
                    'permissions'
                );

            $this->assertTrue(
                $user->hasRole(
                    'advogado-junior'
                )
            );
        } finally {
            setPermissionsTeamId(
                $previousTeamId
            );
        }
    }

    public function test_aceite_reutiliza_usuario_existente(): void
    {
        $existingUser =
            User::factory()
            ->create([
                'name' =>
                'Usuário Existente',

                'email' =>
                'existente@example.com',
            ]);

        [$token] =
            $this->createInvitation(
                $existingUser->email
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}"
            )
            ->assertOk()
            ->assertJsonPath(
                'user.id',
                $existingUser->id
            );

        $this->assertSame(
            1,
            User::query()
                ->where(
                    'email',
                    $existingUser->email
                )
                ->count()
        );
    }

    public function test_aceite_reativa_membership_inativo(): void
    {
        $user =
            User::factory()
            ->create([
                'email' =>
                'inativo@example.com',
            ]);

        $this->organization
            ->users()
            ->attach(
                $user->id,
                [
                    'status' =>
                    'inactive',

                    'joined_at' =>
                    null,
                ]
            );

        [$token] =
            $this->createInvitation(
                $user->email
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}"
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'organization_user',
            [
                'organization_id' =>
                $this->organization->id,

                'user_id' =>
                $user->id,

                'status' =>
                'active',
            ]
        );
    }

    public function test_aceite_marca_convite_como_aceito(): void
    {
        [$token, $invitation] =
            $this->createInvitation(
                'accepted@example.com'
            );

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'name' =>
                    'Accepted Test',

                    'password' =>
                    'password123',

                    'password_confirmation' =>
                    'password123',
                ]
            )
            ->assertOk();

        $invitation->refresh();

        $this->assertSame(
            OrganizationInvitation::STATUS_ACCEPTED,
            $invitation->status
        );

        $this->assertNotNull(
            $invitation->accepted_at
        );
    }

    public function test_convite_nao_pode_ser_aceito_duas_vezes(): void
    {
        [$token] =
            $this->createInvitation(
                'duplo@example.com'
            );

        $payload = [
            'name' =>
            'Duplo Test',

            'password' =>
            'password123',

            'password_confirmation' =>
            'password123',
        ];

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                $payload
            )
            ->assertOk();

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                $payload
            )
            ->assertStatus(410);
    }

    public function test_role_removida_impede_aceite_sem_criar_usuario(): void
    {
        [$token] =
            $this->createInvitation(
                'sem.role@example.com'
            );

        Role::query()
            ->where(
                'organization_id',
                $this->organization->id
            )
            ->where(
                'name',
                'advogado-junior'
            )
            ->delete();

        $this
            ->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'name' =>
                    'Sem Role',

                    'password' =>
                    'password123',

                    'password_confirmation' =>
                    'password123',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);

        $this->assertDatabaseMissing(
            'users',
            [
                'email' =>
                'sem.role@example.com',
            ]
        );
    }

    private function createInvitation(
        string $email,
        array $attributes = [],
    ): array {
        $token =
            Str::random(64);

        $invitation =
            OrganizationInvitation::factory()
            ->for(
                $this->organization
            )
            ->create([
                'email' =>
                $email,

                'role' =>
                'advogado-junior',

                'token_hash' =>
                OrganizationInvitation::hashToken(
                    $token
                ),

                'status' =>
                OrganizationInvitation::STATUS_PENDING,

                'expires_at' =>
                now()->addDay(),

                'accepted_at' =>
                null,

                'revoked_at' =>
                null,

                ...$attributes,
            ]);

        return [
            $token,
            $invitation,
        ];
    }

    public function test_aceite_de_usuario_novo_retorna_token_de_autenticacao(): void
    {
        [$token] =
            $this->createInvitation(
                'autenticado@example.com'
            );

        $response =
            $this->postJson(
                "/api/organization-invitations/accept/{$token}",
                [
                    'name' =>
                    'Usuário Autenticado',

                    'password' =>
                    'password123',

                    'password_confirmation' =>
                    'password123',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ])
            ->assertJsonPath(
                'token_type',
                'bearer'
            );

        $accessToken =
            $response->json(
                'access_token'
            );

        $this->assertIsString(
            $accessToken
        );

        $this->assertNotSame(
            '',
            $accessToken
        );
    }

    public function test_aceite_de_usuario_existente_nao_retorna_token_de_autenticacao(): void
{
    $user =
        User::factory()->create([
            'email' =>
            'existente-sem-login@example.com',
        ]);

    [$token] =
        $this->createInvitation(
            $user->email
        );

    $response =
        $this->postJson(
            "/api/organization-invitations/accept/{$token}"
        );

    $response
        ->assertOk()
        ->assertJsonMissing([
            'access_token',
        ])
        ->assertJsonMissing([
            'token',
        ])
        ->assertJsonMissing([
            'token_type',
        ])
        ->assertJsonMissing([
            'expires_in',
        ]);
}
}
