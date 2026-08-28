<?php

namespace Tests\Feature;

use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrganizationInvitationAdministrationTest extends TestCase
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

        Mail::fake();
    }

    public function test_index_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                '/api/organization-invitations'
            )
            ->assertUnauthorized();
    }

    public function test_index_exige_tenant(): void
    {
        $token =
            auth('api')->login(
                $this->superAdmin()
            );

        $this
            ->withToken(
                $token
            )
            ->getJson(
                '/api/organization-invitations'
            )
            ->assertBadRequest();
    }

    public function test_usuario_sem_permissao_nao_pode_listar_convites(): void
    {
        $user =
            $this->userWithoutPermission();

        $this
            ->asTenant(
                $user
            )
            ->getJson(
                '/api/organization-invitations'
            )
            ->assertForbidden();
    }

    public function test_index_lista_apenas_convites_da_organizacao_atual_em_ordem_decrescente(): void
    {
        $older =
            $this->invitation([
                'email' =>
                'antigo@example.com',

                'created_at' =>
                now()->subDay(),

                'updated_at' =>
                now()->subDay(),
            ]);

        $newer =
            $this->invitation([
                'email' =>
                'recente@example.com',

                'created_at' =>
                now(),

                'updated_at' =>
                now(),
            ]);

        $otherOrganization =
            Organization::factory()
            ->create();

        OrganizationInvitation::factory()
            ->for(
                $otherOrganization
            )
            ->create([
                'email' =>
                'outro-tenant@example.com',
            ]);

        $response =
            $this
            ->asTenant(
                $this->superAdmin()
            )
            ->getJson(
                '/api/organization-invitations'
            );

        $response
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath(
                '0.id',
                $newer->id,
            )
            ->assertJsonPath(
                '1.id',
                $older->id,
            )
            ->assertJsonMissing([
                'email' =>
                'outro-tenant@example.com',
            ]);
    }

    public function test_index_retorna_contrato_administrativo_e_status_efetivo(): void
    {
        $this->invitation([
            'email' =>
            'pendente@example.com',
        ]);

        $this->invitation([
            'email' =>
            'expirado@example.com',

            'expires_at' =>
            now()->subMinute(),
        ]);

        $this->invitation([
            'email' =>
            'aceito@example.com',

            'status' =>
            OrganizationInvitation::STATUS_ACCEPTED,

            'accepted_at' =>
            now(),
        ]);

        $this->invitation([
            'email' =>
            'revogado@example.com',

            'status' =>
            OrganizationInvitation::STATUS_REVOKED,

            'revoked_at' =>
            now(),
        ]);

        $response =
            $this
            ->asTenant(
                $this->superAdmin()
            )
            ->getJson(
                '/api/organization-invitations'
            );

        $response
            ->assertOk()
            ->assertJsonCount(4);

        $invitations =
            collect(
                $response->json()
            )
            ->keyBy(
                'email'
            );

        $this->assertSame(
            'pending',
            $invitations['pendente@example.com']['status'],
        );

        $this->assertSame(
            'expired',
            $invitations['expirado@example.com']['status'],
        );

        $this->assertSame(
            'accepted',
            $invitations['aceito@example.com']['status'],
        );

        $this->assertSame(
            'revoked',
            $invitations['revogado@example.com']['status'],
        );

        $pending =
            $invitations['pendente@example.com'];

        $this->assertSame(
            'advogado-junior',
            $pending['role'],
        );

        $this->assertSame(
            'super-admin@legalis.local',
            $pending['inviter']['email'],
        );

        $this->assertArrayHasKey(
            'expires_at',
            $pending,
        );

        $this->assertArrayHasKey(
            'accepted_at',
            $pending,
        );

        $this->assertArrayHasKey(
            'revoked_at',
            $pending,
        );

        $this->assertArrayHasKey(
            'created_at',
            $pending,
        );

        $this->assertArrayNotHasKey(
            'token',
            $pending,
        );

        $this->assertArrayNotHasKey(
            'token_hash',
            $pending,
        );
    }

    public function test_usuario_sem_permissao_nao_pode_reenviar_convite(): void
    {
        $invitation =
            $this->invitation();

        $this
            ->asTenant(
                $this->userWithoutPermission()
            )
            ->postJson(
                "/api/organization-invitations/{$invitation->id}/resend"
            )
            ->assertForbidden();
    }

    public function test_reenvio_exige_autenticacao(): void
    {
        $invitation =
            $this->invitation();

        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->postJson(
                "/api/organization-invitations/{$invitation->id}/resend"
            )
            ->assertUnauthorized();
    }

    public function test_nao_reenvia_convite_de_outro_tenant(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $invitation =
            OrganizationInvitation::factory()
            ->for(
                $otherOrganization
            )
            ->create();

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->postJson(
                "/api/organization-invitations/{$invitation->id}/resend"
            )
            ->assertNotFound();
    }

    public function test_reenvia_convite_pendente_valido_com_novo_token_e_novo_prazo(): void
    {
        $this->freezeTime();

        $invitation =
            $this->invitation([
                'expires_at' =>
                now()->addDay(),
            ]);

        $oldTokenHash =
            $invitation->token_hash;

        $response =
            $this
            ->asTenant(
                $this->superAdmin()
            )
            ->postJson(
                "/api/organization-invitations/{$invitation->id}/resend"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'id',
                $invitation->id,
            )
            ->assertJsonPath(
                'status',
                'pending',
            )
            ->assertJsonMissingPath(
                'token'
            )
            ->assertJsonMissingPath(
                'token_hash'
            );

        $invitation->refresh();

        $this->assertNotSame(
            $oldTokenHash,
            $invitation->token_hash,
        );

        $expectedExpiresAt =
            now()
            ->addDays(
                OrganizationInvitation::DEFAULT_EXPIRATION_DAYS
            )
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

        $this->assertNull(
            $invitation->accepted_at
        );

        $this->assertNull(
            $invitation->revoked_at
        );

        Mail::assertSent(
            OrganizationInvitationMail::class,
            function (
                OrganizationInvitationMail $mail
            ) use (
                $invitation,
            ): bool {
                return $mail->hasTo(
                    $invitation->email
                )
                    && $invitation->matchesToken(
                        $mail->token
                    );
            }
        );
    }

    public function test_nao_reenvia_convite_expirado(): void
    {
        $invitation =
            $this->invitation([
                'expires_at' =>
                now()->subMinute(),
            ]);

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->postJson(
                "/api/organization-invitations/{$invitation->id}/resend"
            )
            ->assertConflict();

        Mail::assertNothingSent();
    }

    public function test_nao_reenvia_convite_aceito(): void
    {
        $invitation =
            $this->invitation([
                'status' =>
                OrganizationInvitation::STATUS_ACCEPTED,

                'accepted_at' =>
                now(),
            ]);

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->postJson(
                "/api/organization-invitations/{$invitation->id}/resend"
            )
            ->assertConflict();

        Mail::assertNothingSent();
    }

    public function test_nao_reenvia_convite_revogado(): void
    {
        $invitation =
            $this->invitation([
                'status' =>
                OrganizationInvitation::STATUS_REVOKED,

                'revoked_at' =>
                now(),
            ]);

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->postJson(
                "/api/organization-invitations/{$invitation->id}/resend"
            )
            ->assertConflict();

        Mail::assertNothingSent();
    }

    public function test_usuario_sem_permissao_nao_pode_revogar_convite(): void
    {
        $invitation =
            $this->invitation();

        $this
            ->asTenant(
                $this->userWithoutPermission()
            )
            ->patchJson(
                "/api/organization-invitations/{$invitation->id}/revoke"
            )
            ->assertForbidden();
    }

    public function test_revogacao_exige_autenticacao(): void
    {
        $invitation =
            $this->invitation();

        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->patchJson(
                "/api/organization-invitations/{$invitation->id}/revoke"
            )
            ->assertUnauthorized();
    }

    public function test_nao_revoga_convite_de_outro_tenant(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $invitation =
            OrganizationInvitation::factory()
            ->for(
                $otherOrganization
            )
            ->create();

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->patchJson(
                "/api/organization-invitations/{$invitation->id}/revoke"
            )
            ->assertNotFound();
    }

    public function test_revoga_convite_pendente_valido(): void
    {
        $this->freezeTime();

        $invitation =
            $this->invitation();

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->patchJson(
                "/api/organization-invitations/{$invitation->id}/revoke"
            )
            ->assertOk()
            ->assertJsonPath(
                'id',
                $invitation->id,
            )
            ->assertJsonPath(
                'status',
                'revoked',
            )
            ->assertJsonMissingPath(
                'token_hash'
            );

        $invitation->refresh();

        $this->assertSame(
            OrganizationInvitation::STATUS_REVOKED,
            $invitation->status,
        );

        $expectedRevokedAt =
            now()
            ->startOfSecond();

        $actualRevokedAt =
            $invitation
            ->revoked_at
            ->copy()
            ->startOfSecond();

        $this->assertTrue(
            $actualRevokedAt->equalTo(
                $expectedRevokedAt
            )
        );

        $this->assertFalse(
            $invitation->isAcceptable()
        );
    }

    public function test_nao_revoga_convite_expirado(): void
    {
        $invitation =
            $this->invitation([
                'expires_at' =>
                now()->subMinute(),
            ]);

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->patchJson(
                "/api/organization-invitations/{$invitation->id}/revoke"
            )
            ->assertConflict();
    }

    public function test_nao_revoga_convite_aceito(): void
    {
        $invitation =
            $this->invitation([
                'status' =>
                OrganizationInvitation::STATUS_ACCEPTED,

                'accepted_at' =>
                now(),
            ]);

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->patchJson(
                "/api/organization-invitations/{$invitation->id}/revoke"
            )
            ->assertConflict();
    }

    public function test_nao_revoga_convite_ja_revogado(): void
    {
        $invitation =
            $this->invitation([
                'status' =>
                OrganizationInvitation::STATUS_REVOKED,

                'revoked_at' =>
                now(),
            ]);

        $this
            ->asTenant(
                $this->superAdmin()
            )
            ->patchJson(
                "/api/organization-invitations/{$invitation->id}/revoke"
            )
            ->assertConflict();
    }

    private function invitation(
        array $attributes = [],
    ): OrganizationInvitation {
        return OrganizationInvitation::factory()
            ->for(
                $this->organization
            )
            ->create([
                'invited_by' =>
                $this
                    ->superAdmin()
                    ->id,

                ...$attributes,
            ]);
    }

    private function userWithoutPermission(): User
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

        return $user;
    }

    private function superAdmin(): User
    {
        return User::query()
            ->where(
                'email',
                'super-admin@legalis.local',
            )
            ->firstOrFail();
    }

    private function asTenant(
        User $user,
    ): static {
        $token =
            auth('api')->login(
                $user
            );

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
