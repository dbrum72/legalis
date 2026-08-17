<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertUnauthorized();
    }

    public function test_dashboard_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertBadRequest();
    }

    public function test_dashboard_retorna_resumo_da_organizacao_atual(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this->organization
            ->clients()
            ->createMany([
                [
                    'name' =>
                    'Cliente A',

                    'document' =>
                    '11111111111',
                ],
                [
                    'name' =>
                    'Cliente B',

                    'document' =>
                    '22222222222',
                ],
            ]);

        $this->organization
            ->folders()
            ->createMany([
                [
                    'name' =>
                    'Pasta A',

                    'process_number' =>
                    null,
                ],
                [
                    'name' =>
                    'Pasta B',

                    'process_number' =>
                    '5000000-00.2026.8.21.0001',
                ],
                [
                    'name' =>
                    'Pasta C',

                    'process_number' =>
                    null,
                ],
            ]);

        $activeMember =
            User::factory()
            ->create();

        $inactiveMember =
            User::factory()
            ->create();

        $this->attachUser(
            $activeMember,
            $this->organization,
            'active',
        );

        $this->attachUser(
            $inactiveMember,
            $this->organization,
            'inactive',
        );

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'summary.clients',
                2,
            )
            ->assertJsonPath(
                'summary.folders',
                3,
            );

        /*
         * O DatabaseSeeder já vincula usuários
         * ativos à organização padrão.
         *
         * Portanto, não fixamos aqui um número
         * absoluto de membros ativos. Validamos
         * separadamente o comportamento de status.
         */
        $this->assertIsInt(
            $response->json(
                'summary.active_members'
            )
        );
    }

    public function test_dashboard_conta_apenas_membros_ativos(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $before =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        /*
         * Enquanto o endpoint ainda não existe,
         * este teste naturalmente falhará.
         *
         * Depois da implementação, este valor
         * representará o baseline inicial.
         */
        $before
            ->assertOk();

        $initialCount =
            $before->json(
                'summary.active_members'
            );

        $activeMember =
            User::factory()
            ->create();

        $inactiveMember =
            User::factory()
            ->create();

        $this->attachUser(
            $activeMember,
            $this->organization,
            'active',
        );

        $this->attachUser(
            $inactiveMember,
            $this->organization,
            'inactive',
        );

        $after =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $after
            ->assertOk()
            ->assertJsonPath(
                'summary.active_members',
                $initialCount + 1,
            );
    }

    public function test_dashboard_isola_dados_por_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $otherOrganization =
            Organization::factory()
            ->create();

        $this->organization
            ->clients()
            ->create([
                'name' =>
                'Cliente da organização atual',

                'document' =>
                '33333333333',
            ]);

        $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta da organização atual',

                'process_number' =>
                null,
            ]);

        $otherOrganization
            ->clients()
            ->createMany([
                [
                    'name' =>
                    'Cliente externo A',

                    'document' =>
                    '44444444444',
                ],
                [
                    'name' =>
                    'Cliente externo B',

                    'document' =>
                    '55555555555',
                ],
            ]);

        $otherOrganization
            ->folders()
            ->createMany([
                [
                    'name' =>
                    'Pasta externa A',

                    'process_number' =>
                    null,
                ],
                [
                    'name' =>
                    'Pasta externa B',

                    'process_number' =>
                    null,
                ],
            ]);

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertOk()
            ->assertJsonPath(
                'summary.clients',
                1,
            )
            ->assertJsonPath(
                'summary.folders',
                1,
            )
            ->assertJsonMissing([
                'name' =>
                'Pasta externa A',
            ])
            ->assertJsonMissing([
                'name' =>
                'Pasta externa B',
            ]);
    }

    public function test_dashboard_retorna_as_cinco_pastas_mais_recentes(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        foreach (
            range(1, 6) as $index
        ) {
            $folder =
                $this->organization
                ->folders()
                ->create([
                    'name' =>
                    "Pasta {$index}",

                    'process_number' =>
                    $index % 2 === 0
                        ? "500000{$index}-00.2026.8.21.0001"
                        : null,
                ]);

            $folder->forceFill([
                'created_at' =>
                now()->subDays(
                    6 - $index
                ),
            ])->saveQuietly();
        }

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                5,
                'recent_folders',
            )
            ->assertJsonPath(
                'recent_folders.0.name',
                'Pasta 6',
            )
            ->assertJsonPath(
                'recent_folders.1.name',
                'Pasta 5',
            )
            ->assertJsonPath(
                'recent_folders.4.name',
                'Pasta 2',
            )
            ->assertJsonMissing([
                'name' =>
                'Pasta 1',
            ]);
    }

    public function test_dashboard_retorna_contrato_minimo_das_pastas_recentes(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Ação indenizatória',

                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/dashboard'
            )
            ->assertOk()
            ->assertJsonPath(
                'recent_folders.0.id',
                $folder->id,
            )
            ->assertJsonPath(
                'recent_folders.0.name',
                'Ação indenizatória',
            )
            ->assertJsonPath(
                'recent_folders.0.process_number',
                '5000000-00.2026.8.21.0001',
            )
            ->assertJsonStructure([
                'summary' => [
                    'clients',
                    'folders',
                    'active_members',
                ],

                'recent_folders' => [
                    '*' => [
                        'id',
                        'name',
                        'process_number',
                        'created_at',
                    ],
                ],
            ]);
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

    private function attachUser(
        User $user,
        Organization $organization,
        string $status = 'active',
    ): void {
        $organization
            ->users()
            ->syncWithoutDetaching([
                $user->id => [
                    'status' =>
                    $status,

                    'joined_at' =>
                    now(),
                ],
            ]);
    }

    private function asTenant(
        string $token,
        Organization $organization,
    ): static {
        return $this
            ->withToken(
                $token
            )
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            );
    }
}
