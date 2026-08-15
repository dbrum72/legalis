<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationInvitationApiTest extends TestCase
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

    public function test_store_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug
            )
            ->postJson(
                '/api/organization-invitations',
                $this->validPayload()
            )
            ->assertUnauthorized();
    }

    public function test_store_exige_x_tenant(): void
    {
        $token =
            auth('api')->login(
                $this->superAdmin()
            );

        $this
            ->withToken($token)
            ->postJson(
                '/api/organization-invitations',
                $this->validPayload()
            )
            ->assertBadRequest();
    }

    public function test_usuario_sem_permissao_nao_pode_convidar(): void
    {
        $user =
            User::factory()
            ->create();

        $this->organization
            ->users()
            ->attach(
                $user->id,
                [
                    'status' =>
                    'active',

                    'joined_at' =>
                    now(),
                ]
            );

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $this->organization->slug
            )
            ->postJson(
                '/api/organization-invitations',
                $this->validPayload()
            )
            ->assertForbidden();
    }

    public function test_super_admin_pode_criar_convite(): void
    {
        $response =
            $this->postAs(
                $this->superAdmin(),
                $this->validPayload()
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'organization_id',
                $this->organization->id
            )
            ->assertJsonPath(
                'email',
                'convidado@example.com'
            )
            ->assertJsonPath(
                'role',
                'advogado-junior'
            )
            ->assertJsonPath(
                'status',
                OrganizationInvitation::STATUS_PENDING
            );

        $this->assertDatabaseHas(
            'organization_invitations',
            [
                'organization_id' =>
                $this->organization->id,

                'invited_by' =>
                $this
                    ->superAdmin()
                    ->id,

                'email' =>
                'convidado@example.com',

                'role' =>
                'advogado-junior',

                'status' =>
                OrganizationInvitation::STATUS_PENDING,
            ]
        );
    }

    public function test_socio_administrador_pode_criar_convite(): void
    {
        $user =
            User::query()
            ->where(
                'email',
                'socio-administrador@legalis.local'
            )
            ->firstOrFail();

        $this
            ->postAs(
                $user,
                $this->validPayload()
            )
            ->assertCreated();
    }

    public function test_socio_nao_pode_criar_convite(): void
    {
        $user =
            User::query()
            ->where(
                'email',
                'socio@legalis.local'
            )
            ->firstOrFail();

        $this
            ->postAs(
                $user,
                $this->validPayload()
            )
            ->assertForbidden();
    }

    public function test_email_e_obrigatorio(): void
    {
        $payload =
            $this->validPayload();

        unset(
            $payload['email']
        );

        $this
            ->postAs(
                $this->superAdmin(),
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_email_deve_ser_valido(): void
    {
        $this
            ->postAs(
                $this->superAdmin(),
                [
                    'email' =>
                    'email-invalido',

                    'role' =>
                    'advogado-junior',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_email_e_normalizado_para_minusculas(): void
    {
        $this
            ->postAs(
                $this->superAdmin(),
                [
                    'email' =>
                    '  Convidado@Example.COM  ',

                    'role' =>
                    'advogado-junior',
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'email',
                'convidado@example.com'
            );

        $this->assertDatabaseHas(
            'organization_invitations',
            [
                'organization_id' =>
                $this->organization->id,

                'email' =>
                'convidado@example.com',
            ]
        );
    }

    public function test_role_e_obrigatoria(): void
    {
        $payload =
            $this->validPayload();

        unset(
            $payload['role']
        );

        $this
            ->postAs(
                $this->superAdmin(),
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);
    }

    public function test_role_deve_pertencer_a_organizacao_atual(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        Role::query()
            ->create([
                'organization_id' =>
                $otherOrganization->id,

                'name' =>
                'role-exclusiva-outra-organizacao',

                'guard_name' =>
                'api',

                'description' =>
                'Role exclusiva de teste',
            ]);

        $this
            ->postAs(
                $this->superAdmin(),
                [
                    'email' =>
                    'convidado@example.com',

                    'role' =>
                    'role-exclusiva-outra-organizacao',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);
    }

    public function test_payload_nao_pode_escolher_organizacao_nem_autor(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $otherUser =
            User::factory()
            ->create();

        $response =
            $this->postAs(
                $this->superAdmin(),
                [
                    ...$this->validPayload(),

                    'organization_id' =>
                    $otherOrganization->id,

                    'invited_by' =>
                    $otherUser->id,

                    'status' =>
                    OrganizationInvitation::STATUS_ACCEPTED,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'organization_id',
                $this->organization->id
            )
            ->assertJsonPath(
                'invited_by',
                $this
                    ->superAdmin()
                    ->id
            )
            ->assertJsonPath(
                'status',
                OrganizationInvitation::STATUS_PENDING
            );
    }

    public function test_usuario_com_membership_ativo_nao_pode_ser_convidado(): void
    {
        $existingUser =
            User::query()
            ->where(
                'email',
                'advogado-junior@legalis.local'
            )
            ->firstOrFail();

        $this->assertTrue(
            $this->organization
                ->users()
                ->whereKey(
                    $existingUser->id
                )
                ->wherePivot(
                    'status',
                    'active'
                )
                ->exists()
        );

        $this
            ->postAs(
                $this->superAdmin(),
                [
                    'email' =>
                    $existingUser->email,

                    'role' =>
                    'advogado-junior',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);

        $this->assertDatabaseMissing(
            'organization_invitations',
            [
                'organization_id' =>
                $this->organization->id,

                'email' =>
                $existingUser->email,
            ]
        );
    }

    public function test_convite_pendente_nao_expirado_nao_pode_ser_duplicado(): void
    {
        OrganizationInvitation::factory()
            ->for(
                $this->organization
            )
            ->create([
                'email' =>
                'convidado@example.com',

                'status' =>
                OrganizationInvitation::STATUS_PENDING,

                'expires_at' =>
                now()->addDay(),
            ]);

        $this
            ->postAs(
                $this->superAdmin(),
                $this->validPayload()
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);

        $this->assertSame(
            1,
            $this->organization
                ->invitations()
                ->where(
                    'email',
                    'convidado@example.com'
                )
                ->count()
        );
    }

    public function test_convite_pendente_expirado_nao_impede_novo_convite(): void
    {
        OrganizationInvitation::factory()
            ->expired()
            ->for(
                $this->organization
            )
            ->create([
                'email' =>
                'convidado@example.com',
            ]);

        $this
            ->postAs(
                $this->superAdmin(),
                $this->validPayload()
            )
            ->assertCreated();

        $this->assertSame(
            2,
            $this->organization
                ->invitations()
                ->where(
                    'email',
                    'convidado@example.com'
                )
                ->count()
        );
    }

    public function test_resposta_nao_expoe_token_hash(): void
    {
        $response =
            $this->postAs(
                $this->superAdmin(),
                $this->validPayload()
            );

        $response->assertCreated();

        $this->assertArrayNotHasKey(
            'token_hash',
            $response->json()
        );

        $invitation =
            OrganizationInvitation::query()
            ->where(
                'email',
                'convidado@example.com'
            )
            ->firstOrFail();

        $this->assertNotEmpty(
            $invitation->token_hash
        );

        $this->assertSame(
            64,
            strlen(
                $invitation->token_hash
            )
        );
    }

    public function test_convite_recebe_prazo_padrao_de_sete_dias(): void
    {
        $before =
            now()
            ->addDays(
                OrganizationInvitation::DEFAULT_EXPIRATION_DAYS
            )
            ->subMinute();

        $response =
            $this->postAs(
                $this->superAdmin(),
                $this->validPayload()
            );

        $response->assertCreated();

        $after =
            now()
            ->addDays(
                OrganizationInvitation::DEFAULT_EXPIRATION_DAYS
            )
            ->addMinute();

        $invitation =
            OrganizationInvitation::query()
            ->where(
                'email',
                'convidado@example.com'
            )
            ->firstOrFail();

        $this->assertTrue(
            $invitation
                ->expires_at
                ->between(
                    $before,
                    $after
                )
        );
    }

    private function validPayload(): array
    {
        return [
            'email' =>
            'convidado@example.com',

            'role' =>
            'advogado-junior',
        ];
    }

    private function postAs(
        User $user,
        array $payload,
    ) {
        $token =
            auth('api')->login(
                $user
            );

        return $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $this->organization->slug
            )
            ->postJson(
                '/api/organization-invitations',
                $payload
            );
    }

    private function superAdmin(): User
    {
        return User::query()
            ->where(
                'email',
                'super-admin@legalis.local'
            )
            ->firstOrFail();
    }
}
