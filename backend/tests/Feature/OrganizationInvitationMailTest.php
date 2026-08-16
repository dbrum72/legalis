<?php

namespace Tests\Feature;

use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\OrganizationInvitations\IssueOrganizationInvitation;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrganizationInvitationMailTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $inviter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            DatabaseSeeder::class
        );

        config([
            'legalis.frontend_url' =>
            'http://localhost:5173',
        ]);

        $this->organization =
            Organization::query()
            ->where(
                'slug',
                OrganizationSeeder::DEFAULT_SLUG
            )
            ->firstOrFail();

        $this->inviter =
            User::query()
            ->where(
                'email',
                'super-admin@legalis.local'
            )
            ->firstOrFail();
    }

    public function test_emissao_envia_email_para_destinatario(): void
    {
        Mail::fake();

        $service =
            app(
                IssueOrganizationInvitation::class
            );

        $invitation =
            $service->execute(
                organization: $this->organization,

                inviter: $this->inviter,

                email: 'convidado@example.com',

                role: 'advogado-junior',
            );

        Mail::assertSent(
            OrganizationInvitationMail::class,
            function (
                OrganizationInvitationMail $mail
            ) use ($invitation): bool {
                return $mail->hasTo(
                    'convidado@example.com'
                )
                    && $mail
                    ->invitation
                    ->is(
                        $invitation
                    );
            }
        );
    }

    public function test_email_contem_link_com_token_bruto(): void
    {
        Mail::fake();

        app(
            IssueOrganizationInvitation::class
        )->execute(
            organization: $this->organization,

            inviter: $this->inviter,

            email: 'convidado@example.com',

            role: 'advogado-junior',
        );

        Mail::assertSent(
            OrganizationInvitationMail::class,
            function (
                OrganizationInvitationMail $mail
            ): bool {
                $this->assertStringStartsWith(
                    'http://localhost:5173/invitations/accept/',
                    $mail->acceptanceUrl
                );

                $this->assertNotSame(
                    'http://localhost:5173/invitations/accept/',
                    $mail->acceptanceUrl
                );

                return true;
            }
        );
    }

    public function test_token_bruto_do_email_corresponde_ao_hash_persistido(): void
    {
        Mail::fake();

        $invitation =
            app(
                IssueOrganizationInvitation::class
            )->execute(
                organization: $this->organization,

                inviter: $this->inviter,

                email: 'convidado@example.com',

                role: 'advogado-junior',
            );

        Mail::assertSent(
            OrganizationInvitationMail::class,
            function (
                OrganizationInvitationMail $mail
            ) use ($invitation): bool {
                $this->assertTrue(
                    $invitation->matchesToken(
                        $mail->token
                    )
                );

                $this->assertNotSame(
                    $mail->token,
                    $invitation->token_hash
                );

                return true;
            }
        );
    }

    public function test_email_contem_organizacao_funcao_e_remetente(): void
    {
        Mail::fake();

        app(
            IssueOrganizationInvitation::class
        )->execute(
            organization: $this->organization,

            inviter: $this->inviter,

            email: 'convidado@example.com',

            role: 'advogado-junior',
        );

        Mail::assertSent(
            OrganizationInvitationMail::class,
            function (
                OrganizationInvitationMail $mail
            ): bool {
                $mail->assertHasSubject(
                    'Convite para acessar '
                        . $this
                        ->organization
                        ->name
                );

                $mail->assertSeeInHtml(
                    $this
                        ->organization
                        ->name
                );

                $mail->assertSeeInHtml(
                    'advogado-junior'
                );

                $mail->assertSeeInHtml(
                    $this
                        ->inviter
                        ->name
                );

                return true;
            }
        );
    }

    public function test_email_contem_url_de_aceite_no_html(): void
    {
        Mail::fake();

        app(
            IssueOrganizationInvitation::class
        )->execute(
            organization: $this->organization,

            inviter: $this->inviter,

            email: 'convidado@example.com',

            role: 'advogado-junior',
        );

        Mail::assertSent(
            OrganizationInvitationMail::class,
            function (
                OrganizationInvitationMail $mail
            ): bool {
                $mail->assertSeeInHtml(
                    $mail->acceptanceUrl,
                    false
                );

                return true;
            }
        );
    }

    public function test_token_hash_nao_e_exposto_no_corpo_do_email(): void
    {
        Mail::fake();

        $invitation =
            app(
                IssueOrganizationInvitation::class
            )->execute(
                organization: $this->organization,

                inviter: $this->inviter,

                email: 'convidado@example.com',

                role: 'advogado-junior',
            );

        Mail::assertSent(
            OrganizationInvitationMail::class,
            function (
                OrganizationInvitationMail $mail
            ) use ($invitation): bool {
                $mail->assertDontSeeInHtml(
                    $invitation->token_hash
                );

                return true;
            }
        );
    }

    public function test_api_de_emissao_dispara_email(): void
    {
        Mail::fake();

        $token =
            auth('api')->login(
                $this->inviter
            );

        $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $this->organization->slug
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'convidado@example.com',

                    'role' =>
                    'advogado-junior',
                ]
            )
            ->assertCreated();

        Mail::assertSent(
            OrganizationInvitationMail::class,
            fn(
                OrganizationInvitationMail $mail
            ): bool =>
            $mail->hasTo(
                'convidado@example.com'
            )
        );
    }

    public function test_resposta_da_api_continua_sem_token_hash_e_token_bruto(): void
    {
        Mail::fake();

        $token =
            auth('api')->login(
                $this->inviter
            );

        $response =
            $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $this->organization->slug
            )
            ->postJson(
                '/api/organization-invitations',
                [
                    'email' =>
                    'convidado@example.com',

                    'role' =>
                    'advogado-junior',
                ]
            );

        $response->assertCreated();

        $this->assertArrayNotHasKey(
            'token',
            $response->json()
        );

        $this->assertArrayNotHasKey(
            'token_hash',
            $response->json()
        );
    }

    public function test_service_persiste_convite_pendente(): void
    {
        Mail::fake();

        $invitation =
            app(
                IssueOrganizationInvitation::class
            )->execute(
                organization: $this->organization,

                inviter: $this->inviter,

                email: 'convidado@example.com',

                role: 'advogado-junior',
            );

        $this->assertDatabaseHas(
            'organization_invitations',
            [
                'id' =>
                $invitation->id,

                'organization_id' =>
                $this->organization->id,

                'email' =>
                'convidado@example.com',

                'status' =>
                OrganizationInvitation::STATUS_PENDING,
            ]
        );
    }
}
