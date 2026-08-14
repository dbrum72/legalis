<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_organizacao_com_factory(): void
    {
        $organization =
            Organization::factory()->create();

        $this->assertDatabaseHas(
            'organizations',
            [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'status' => 'active',
            ],
        );
    }

    public function test_organizacao_pode_ter_varios_usuarios(): void
    {
        $organization =
            Organization::factory()->create();

        $users =
            User::factory()
            ->count(2)
            ->create();

        $organization
            ->users()
            ->attach(
                $users->pluck('id'),
                [
                    'status' => 'active',
                ],
            );

        $organization->load('users');

        $this->assertCount(
            2,
            $organization->users,
        );

        $this->assertTrue(
            $organization->users
                ->contains($users[0]),
        );

        $this->assertTrue(
            $organization->users
                ->contains($users[1]),
        );
    }

    public function test_usuario_pode_pertencer_a_varias_organizacoes(): void
    {
        $user =
            User::factory()->create();

        $organizations =
            Organization::factory()
            ->count(2)
            ->create();

        $user
            ->organizations()
            ->attach(
                $organizations->pluck('id'),
                [
                    'status' => 'active',
                ],
            );

        $user->load('organizations');

        $this->assertCount(
            2,
            $user->organizations,
        );

        $this->assertTrue(
            $user->organizations
                ->contains($organizations[0]),
        );

        $this->assertTrue(
            $user->organizations
                ->contains($organizations[1]),
        );
    }

    public function test_membership_possui_status_proprio(): void
    {
        $user =
            User::factory()->create();

        $organization =
            Organization::factory()->create();

        $user
            ->organizations()
            ->attach(
                $organization->id,
                [
                    'status' => 'inactive',
                ],
            );

        $membershipOrganization =
            $user
            ->organizations()
            ->firstOrFail();

        $this->assertSame(
            'inactive',
            $membershipOrganization
                ->membership
                ->status,
        );
    }

    public function test_mesmo_usuario_nao_e_duplicado_na_organizacao(): void
    {
        $user =
            User::factory()->create();

        $organization =
            Organization::factory()->create();

        $organization
            ->users()
            ->syncWithPivotValues(
                [$user->id],
                [
                    'status' => 'active',
                ],
                false,
            );

        $organization
            ->users()
            ->syncWithPivotValues(
                [$user->id],
                [
                    'status' => 'active',
                ],
                false,
            );

        $this->assertDatabaseCount(
            'organization_user',
            1,
        );
    }

    public function test_organizacao_pode_ser_inativa(): void
    {
        $organization =
            Organization::factory()
            ->inactive()
            ->create();

        $this->assertSame(
            'inactive',
            $organization->status,
        );
    }

    public function test_database_seeder_cria_organizacao_padrao(): void
    {
        $this->seed(
            DatabaseSeeder::class,
        );

        $this->assertDatabaseHas(
            'organizations',
            [
                'name' =>
                OrganizationSeeder::DEFAULT_NAME,

                'slug' =>
                OrganizationSeeder::DEFAULT_SLUG,

                'status' =>
                'active',
            ],
        );
    }

    public function test_database_seeder_vincula_usuarios_a_organizacao_padrao(): void
    {
        $this->seed(
            DatabaseSeeder::class,
        );

        $organization =
            Organization::query()
            ->where(
                'slug',
                OrganizationSeeder::DEFAULT_SLUG,
            )
            ->firstOrFail();

        $usersCount =
            User::query()->count();

        $this->assertGreaterThan(
            0,
            $usersCount,
        );

        $this->assertSame(
            $usersCount,
            $organization
                ->users()
                ->count(),
        );

        $this->assertDatabaseMissing(
            'organization_user',
            [
                'organization_id' =>
                $organization->id,

                'status' =>
                'inactive',
            ],
        );
    }
}
