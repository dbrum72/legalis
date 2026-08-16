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

class OrganizationInvitationStoreTest extends TestCase
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

    public function test_store_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertUnauthorized();
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
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'organization_invitations',
            [
                'email' =>
                'novo@legalis.local',
            ]
        );
    }

    public function test_super_admin_pode_criar_convite(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'organization_id',
                $this->organization->id,
            )
            ->assertJsonPath(
                'email',
                'novo@legalis.local',
            )
            ->assertJsonPath(
                'role',
                'advogado-junior',
            )
            ->assertJsonPath(
                'status',
                OrganizationInvitation::STATUS_PENDING,
            )
            ->assertJsonPath(
                'organization.id',
                $this->organization->id,
            )
            ->assertJsonPath(
                'inviter.email',
                'super-admin@legalis.local',
            )
            ->assertJsonMissingPath(
                'token_hash'
            );

        $this->assertDatabaseHas(
            'organization_invitations',
            [
                'organization_id' =>
                $this->organization->id,

                'email' =>
                'novo@legalis.local',

                'role' =>
                'advogado-junior',

                'status' =>
                OrganizationInvitation::STATUS_PENDING,
            ]
        );
    }

    public function test_payload_nao_pode_escolher_organization_id(): void
    {
        $otherOrganization =
            Organization::query()
            ->whereKeyNot(
                $this->organization->id
            )
            ->firstOrFail();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'organization_id' =>
                    $otherOrganization->id,

                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'organization_id',
                $this->organization->id,
            );

        $this->assertDatabaseHas(
            'organization_invitations',
            [
                'organization_id' =>
                $this->organization->id,

                'email' =>
                'novo@legalis.local',
            ]
        );

        $this->assertDatabaseMissing(
            'organization_invitations',
            [
                'organization_id' =>
                $otherOrganization->id,

                'email' =>
                'novo@legalis.local',
            ]
        );
    }

    public function test_email_e_normalizado(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    '  NOVO@LEGALIS.LOCAL  ',

                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'email',
                'novo@legalis.local',
            );

        $this->assertDatabaseHas(
            'organization_invitations',
            [
                'organization_id' =>
                $this->organization->id,

                'email' =>
                'novo@legalis.local',
            ]
        );
    }

    public function test_email_e_obrigatorio(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_email_deve_ser_valido(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'email-invalido',

                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_role_e_obrigatoria(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);
    }

    public function test_role_deve_existir_na_organizacao_atual(): void
    {
        $otherOrganization =
            Organization::query()
            ->whereKeyNot(
                $this->organization->id
            )
            ->firstOrFail();

        Role::query()
            ->create([
                'organization_id' =>
                $otherOrganization->id,

                'name' =>
                'role-exclusiva-outra-organizacao',

                'guard_name' =>
                'api',

                'description' =>
                'Role exclusiva para teste',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'role-exclusiva-outra-organizacao',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'role',
            ]);
    }

    public function test_nao_permite_convite_pendente_valido_duplicado(): void
    {
        OrganizationInvitation::factory()
            ->for(
                $this->organization
            )
            ->create([
                'email' =>
                'novo@legalis.local',

                'role' =>
                'advogado-junior',

                'status' =>
                OrganizationInvitation::STATUS_PENDING,

                'expires_at' =>
                now()->addDay(),
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);

        $this->assertSame(
            1,
            OrganizationInvitation::query()
                ->where(
                    'organization_id',
                    $this->organization->id,
                )
                ->where(
                    'email',
                    'novo@legalis.local',
                )
                ->count()
        );
    }

    public function test_convite_expirado_nao_bloqueia_novo_convite(): void
    {
        OrganizationInvitation::factory()
            ->for(
                $this->organization
            )
            ->expired()
            ->create([
                'email' =>
                'novo@legalis.local',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertCreated();

        $this->assertSame(
            2,
            OrganizationInvitation::query()
                ->where(
                    'organization_id',
                    $this->organization->id,
                )
                ->where(
                    'email',
                    'novo@legalis.local',
                )
                ->count()
        );
    }

    public function test_usuario_com_membership_ativo_nao_pode_ser_convidado(): void
    {
        $member =
            User::factory()
            ->create([
                'email' =>
                'membro@legalis.local',
            ]);

        $this->organization
            ->users()
            ->attach(
                $member->id,
                [
                    'status' =>
                    'active',

                    'joined_at' =>
                    now(),
                ]
            );

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'membro@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
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
                'membro@legalis.local',
            ]
        );
    }

    public function test_token_hash_e_gerado_e_nao_exposto(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonMissingPath(
                'token_hash'
            );

        $invitation =
            OrganizationInvitation::query()
            ->where(
                'organization_id',
                $this->organization->id,
            )
            ->where(
                'email',
                'novo@legalis.local',
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

    public function test_convite_expira_em_aproximadamente_sete_dias(): void
    {
        $this->freezeTime();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'novo@legalis.local',

                    'role' =>
                    'advogado-junior',
                ],
            )
            ->assertCreated();

        $invitation =
            OrganizationInvitation::query()
            ->where(
                'organization_id',
                $this->organization->id,
            )
            ->where(
                'email',
                'novo@legalis.local',
            )
            ->firstOrFail();

        $expectedExpiresAt =
            now()
            ->addDays(7)
            ->startOfSecond();

        $actualExpiresAt =
            $invitation
            ->expires_at
            ->copy()
            ->startOfSecond();

        $this->assertTrue(
            $actualExpiresAt->equalTo(
                $expectedExpiresAt
            )
        );
    }

    private function loginAsSuperAdmin(): string
    {
        $user =
            User::query()
            ->where(
                'email',
                'super-admin@legalis.local',
            )
            ->firstOrFail();

        return auth('api')->login(
            $user
        );
    }

    private function asTenant(
        string $token,
    ): static {
        return $this
            ->withToken(
                $token
            )
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            );
    }
}
