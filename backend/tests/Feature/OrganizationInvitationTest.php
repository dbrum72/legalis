<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_convite_com_factory(): void
    {
        $invitation =
            OrganizationInvitation::factory()
            ->create();

        $this->assertDatabaseHas(
            'organization_invitations',
            [
                'id' =>
                $invitation->id,

                'organization_id' =>
                $invitation->organization_id,

                'email' =>
                $invitation->email,

                'role' =>
                'advogado-junior',

                'status' =>
                OrganizationInvitation::STATUS_PENDING,
            ]
        );
    }

    public function test_convite_pertence_a_organizacao(): void
    {
        $organization =
            Organization::factory()
            ->create();

        $invitation =
            OrganizationInvitation::factory()
            ->for(
                $organization
            )
            ->create();

        $this->assertTrue(
            $invitation
                ->organization
                ->is(
                    $organization
                )
        );
    }

    public function test_organizacao_possui_convites(): void
    {
        $organization =
            Organization::factory()
            ->create();

        OrganizationInvitation::factory()
            ->count(2)
            ->for(
                $organization
            )
            ->create();

        $this->assertCount(
            2,
            $organization
                ->invitations()
                ->get()
        );
    }

    public function test_convite_registra_usuario_que_convidou(): void
    {
        $user =
            User::factory()
            ->create();

        $invitation =
            OrganizationInvitation::factory()
            ->create([
                'invited_by' =>
                $user->id,
            ]);

        $this->assertTrue(
            $invitation
                ->inviter
                ->is(
                    $user
                )
        );

        $this->assertTrue(
            $user
                ->sentOrganizationInvitations()
                ->whereKey(
                    $invitation->id
                )
                ->exists()
        );
    }

    public function test_convite_pendente_e_identificado_corretamente(): void
    {
        $invitation =
            OrganizationInvitation::factory()
            ->create();

        $this->assertTrue(
            $invitation->isPending()
        );

        $this->assertFalse(
            $invitation->isAccepted()
        );

        $this->assertFalse(
            $invitation->isRevoked()
        );
    }

    public function test_convite_aceito_e_identificado_corretamente(): void
    {
        $invitation =
            OrganizationInvitation::factory()
            ->accepted()
            ->create();

        $this->assertFalse(
            $invitation->isPending()
        );

        $this->assertTrue(
            $invitation->isAccepted()
        );

        $this->assertFalse(
            $invitation->isRevoked()
        );

        $this->assertNotNull(
            $invitation->accepted_at
        );
    }

    public function test_convite_revogado_e_identificado_corretamente(): void
    {
        $invitation =
            OrganizationInvitation::factory()
            ->revoked()
            ->create();

        $this->assertFalse(
            $invitation->isPending()
        );

        $this->assertFalse(
            $invitation->isAccepted()
        );

        $this->assertTrue(
            $invitation->isRevoked()
        );

        $this->assertNotNull(
            $invitation->revoked_at
        );
    }

    public function test_convite_expirado_e_identificado_corretamente(): void
    {
        $invitation =
            OrganizationInvitation::factory()
            ->expired()
            ->create();

        $this->assertTrue(
            $invitation->isPending()
        );

        $this->assertTrue(
            $invitation->isExpired()
        );

        $this->assertFalse(
            $invitation->isAcceptable()
        );
    }

    public function test_convite_pendente_e_nao_expirado_pode_ser_aceito(): void
    {
        $invitation =
            OrganizationInvitation::factory()
            ->create([
                'expires_at' =>
                now()->addHour(),
            ]);

        $this->assertTrue(
            $invitation->isPending()
        );

        $this->assertFalse(
            $invitation->isExpired()
        );

        $this->assertTrue(
            $invitation->isAcceptable()
        );
    }

    public function test_convite_aceito_nao_pode_ser_aceito_novamente(): void
    {
        $invitation =
            OrganizationInvitation::factory()
            ->accepted()
            ->create();

        $this->assertFalse(
            $invitation->isAcceptable()
        );
    }

    public function test_convite_revogado_nao_pode_ser_aceito(): void
    {
        $invitation =
            OrganizationInvitation::factory()
            ->revoked()
            ->create();

        $this->assertFalse(
            $invitation->isAcceptable()
        );
    }

    public function test_token_e_armazenado_com_hash_sha256(): void
    {
        $token =
            Str::random(64);

        $hash =
            OrganizationInvitation::hashToken(
                $token
            );

        $this->assertSame(
            64,
            strlen($hash)
        );

        $this->assertSame(
            hash(
                'sha256',
                $token
            ),
            $hash
        );
    }

    public function test_matches_token_valida_token_correto(): void
    {
        $token =
            Str::random(64);

        $invitation =
            OrganizationInvitation::factory()
            ->create([
                'token_hash' =>
                OrganizationInvitation::hashToken(
                    $token
                ),
            ]);

        $this->assertTrue(
            $invitation->matchesToken(
                $token
            )
        );
    }

    public function test_matches_token_rejeita_token_incorreto(): void
    {
        $token =
            Str::random(64);

        $invitation =
            OrganizationInvitation::factory()
            ->create([
                'token_hash' =>
                OrganizationInvitation::hashToken(
                    $token
                ),
            ]);

        $this->assertFalse(
            $invitation->matchesToken(
                'token-invalido'
            )
        );
    }

    public function test_token_hash_e_unico_no_banco(): void
    {
        $hash =
            OrganizationInvitation::hashToken(
                Str::random(64)
            );

        OrganizationInvitation::factory()
            ->create([
                'token_hash' =>
                $hash,
            ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        OrganizationInvitation::factory()
            ->create([
                'token_hash' =>
                $hash,
            ]);
    }

    public function test_convites_de_organizacoes_diferentes_ficam_separados_pela_relacao(): void
    {
        $organizationA =
            Organization::factory()
            ->create();

        $organizationB =
            Organization::factory()
            ->create();

        $invitationA =
            OrganizationInvitation::factory()
            ->for(
                $organizationA
            )
            ->create();

        $invitationB =
            OrganizationInvitation::factory()
            ->for(
                $organizationB
            )
            ->create();

        $this->assertTrue(
            $organizationA
                ->invitations()
                ->whereKey(
                    $invitationA->id
                )
                ->exists()
        );

        $this->assertFalse(
            $organizationA
                ->invitations()
                ->whereKey(
                    $invitationB->id
                )
                ->exists()
        );

        $this->assertTrue(
            $organizationB
                ->invitations()
                ->whereKey(
                    $invitationB->id
                )
                ->exists()
        );
    }
}
