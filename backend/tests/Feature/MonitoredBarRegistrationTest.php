<?php

namespace Tests\Feature;

use App\Jobs\SyncDjenBarRegistration;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MonitoredBarRegistrationTest extends TestCase
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

    public function test_cadastro_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->postJson(
                '/api/monitored-bar-registrations',
                $this->validPayload(),
            )
            ->assertUnauthorized();
    }

    public function test_administrador_cadastra_inscricao_oab_monitorada(): void
    {
        $response =
            $this
                ->asTenant(
                    $this->login(
                        'super-admin@legalis.local'
                    )
                )
                ->postJson(
                    '/api/monitored-bar-registrations',
                    [
                        'lawyer_name' => '  Maria   da Silva  ',

                        'bar_number' => ' 93.556-a ',

                        'state' => 'rs',

                        'monitoring_started_on' => '2026-08-28',
                    ],
                );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'lawyer_name',
                'Maria da Silva',
            )
            ->assertJsonPath(
                'bar_number',
                '93556A',
            )
            ->assertJsonPath(
                'state',
                'RS',
            )
            ->assertJsonPath(
                'active',
                true,
            );

        $this->assertDatabaseHas(
            'monitored_bar_registrations',
            [
                'organization_id' => $this->organization->id,

                'bar_number' => '93556A',

                'state' => 'RS',
            ],
        );
    }

    public function test_inscricao_oab_e_unica_por_organizacao_e_uf(): void
    {
        $this->organization
            ->monitoredBarRegistrations()
            ->create(
                $this->validPayload()
            );

        $this
            ->asTenant(
                $this->login(
                    'super-admin@legalis.local'
                )
            )
            ->postJson(
                '/api/monitored-bar-registrations',
                $this->validPayload()
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'bar_number',
            ]);
    }

    public function test_advogado_pode_visualizar_mas_nao_administrar_monitoramento(): void
    {
        $token =
            $this->login(
                'advogado-pleno@legalis.local'
            );

        $this
            ->asTenant($token)
            ->getJson(
                '/api/monitored-bar-registrations'
            )
            ->assertOk();

        $this
            ->asTenant($token)
            ->postJson(
                '/api/monitored-bar-registrations',
                $this->validPayload(),
            )
            ->assertForbidden();
    }

    public function test_inscricoes_ficam_isoladas_por_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()
                ->create();

        $otherOrganization
            ->monitoredBarRegistrations()
            ->create([
                'lawyer_name' => 'Advogado de outra organização',

                'bar_number' => '12345',

                'state' => 'SC',
            ]);

        $this
            ->asTenant(
                $this->login(
                    'super-admin@legalis.local'
                )
            )
            ->getJson(
                '/api/monitored-bar-registrations'
            )
            ->assertOk()
            ->assertJsonMissing([
                'lawyer_name' => 'Advogado de outra organização',
            ]);
    }

    public function test_sincronizacao_manual_e_enfileirada(): void
    {
        Queue::fake();

        $registration =
            $this->organization
                ->monitoredBarRegistrations()
                ->create(
                    $this->validPayload()
                );

        $this
            ->asTenant(
                $this->login(
                    'super-admin@legalis.local'
                )
            )
            ->postJson(
                "/api/monitored-bar-registrations/{$registration->id}/sync",
                [
                    'from' => '2026-08-25',

                    'to' => '2026-08-28',
                ],
            )
            ->assertStatus(202)
            ->assertJsonPath(
                'period_start',
                '2026-08-25',
            )
            ->assertJsonPath(
                'period_end',
                '2026-08-28',
            );

        Queue::assertPushed(
            SyncDjenBarRegistration::class,
            fn (SyncDjenBarRegistration $job): bool => $job->barRegistrationId
                    === $registration->id
                && $job->periodStart
                    === '2026-08-25'
                && $job->periodEnd
                    === '2026-08-28',
        );
    }

    public function test_inscricao_inativa_nao_pode_ser_sincronizada(): void
    {
        Queue::fake();

        $registration =
            $this->organization
                ->monitoredBarRegistrations()
                ->create([
                    ...$this->validPayload(),
                    'active' => false,
                ]);

        $this
            ->asTenant(
                $this->login(
                    'super-admin@legalis.local'
                )
            )
            ->postJson(
                "/api/monitored-bar-registrations/{$registration->id}/sync"
            )
            ->assertUnprocessable();

        Queue::assertNothingPushed();
    }

    public function test_sincronizacao_preserva_data_inicial_no_fuso_juridico(): void
    {
        Queue::fake();

        $registration =
            $this->organization
                ->monitoredBarRegistrations()
                ->create([
                    ...$this->validPayload(),
                    'monitoring_started_on' => '2026-08-28',
                ]);

        $this
            ->asTenant(
                $this->login(
                    'super-admin@legalis.local'
                )
            )
            ->postJson(
                "/api/monitored-bar-registrations/{$registration->id}/sync",
                [
                    'to' => '2026-08-28',
                ],
            )
            ->assertStatus(202)
            ->assertJsonPath(
                'period_start',
                '2026-08-28',
            );
    }

    private function validPayload(): array
    {
        return [
            'lawyer_name' => 'Advogada Monitorada',

            'bar_number' => '93556',

            'state' => 'RS',

            'active' => true,
        ];
    }

    private function login(
        string $email,
    ): string {
        $user =
            User::query()
                ->where(
                    'email',
                    $email,
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
