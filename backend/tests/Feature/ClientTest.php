<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\MaritalStatus;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientTest extends TestCase
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

    public function test_index_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson('/api/clients')
            ->assertUnauthorized();
    }

    public function test_usuario_sem_permissao_nao_pode_listar_clientes(): void
    {
        $user =
            User::factory()->create();

        $this->attachUser(
            $user,
            $this->organization,
        );

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson('/api/clients')
            ->assertForbidden();
    }

    public function test_usuario_com_permissao_pode_listar_clientes(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        Client::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Cliente do escritório',
            ]);

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson('/api/clients')
            ->assertOk()
            ->assertJsonFragment([
                'name' =>
                'Cliente do escritório',
            ]);
    }

    public function test_index_retorna_apenas_clientes_da_organizacao_atual(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $otherOrganization =
            Organization::factory()
            ->create();

        Client::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Cliente Organização A',
            ]);

        Client::factory()
            ->for(
                $otherOrganization
            )
            ->create([
                'name' =>
                'Cliente Organização B',
            ]);

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                '/api/clients'
            );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'name' =>
                'Cliente Organização A',
            ])
            ->assertJsonMissing([
                'name' =>
                'Cliente Organização B',
            ]);
    }

    public function test_cria_cliente_na_organizacao_atual(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $maritalStatus =
            MaritalStatus::firstOrFail();

        $payload = [
            'name' =>
            'Cliente Teste',

            'document' =>
            '12345678901',

            'identity_document' =>
            '123456789',

            'identity_issuer' =>
            'SSP',

            'marital_status_id' =>
            $maritalStatus->id,

            'profession' =>
            'Advogado',

            'address' =>
            'Rua Teste, 100',

            'address_complement' =>
            'Sala 2',

            'district' =>
            'Centro',

            'city' =>
            'Pelotas',

            'postal_code' =>
            '96000000',

            'phone' =>
            '53999999999',

            'whatsapp' =>
            true,

            'email' =>
            'cliente@teste.com',
        ];

        $response =
            $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                '/api/clients',
                $payload,
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'name',
                'Cliente Teste',
            )
            ->assertJsonPath(
                'document',
                '12345678901',
            )
            ->assertJsonPath(
                'organization_id',
                $this->organization->id,
            )
            ->assertJsonPath(
                'marital_status.id',
                $maritalStatus->id,
            );

        $this->assertDatabaseHas(
            'clients',
            [
                'organization_id' =>
                $this->organization->id,

                'document' =>
                '12345678901',
            ],
        );
    }

    public function test_payload_nao_pode_escolher_organization_id(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $otherOrganization =
            Organization::factory()
            ->create();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                '/api/clients',
                [
                    'organization_id' =>
                    $otherOrganization->id,

                    'name' =>
                    'Cliente Teste',

                    'document' =>
                    '12345678901',
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'organization_id',
                $this->organization->id,
            );

        $this->assertDatabaseHas(
            'clients',
            [
                'organization_id' =>
                $this->organization->id,

                'document' =>
                '12345678901',
            ],
        );

        $this->assertDatabaseMissing(
            'clients',
            [
                'organization_id' =>
                $otherOrganization->id,

                'document' =>
                '12345678901',
            ],
        );
    }

    public function test_documento_deve_ser_unico_na_mesma_organizacao(): void
    {
        Client::factory()
            ->for(
                $this->organization
            )
            ->create([
                'document' =>
                '12345678901',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                '/api/clients',
                [
                    'name' =>
                    'Outro Cliente',

                    'document' =>
                    '12345678901',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'document',
            ]);
    }

    public function test_mesmo_documento_pode_existir_em_outra_organizacao(): void
    {
        Client::factory()
            ->for(
                $this->organization
            )
            ->create([
                'document' =>
                '12345678901',
            ]);

        $user =
            $this->superAdmin();

        $otherOrganization =
            $this
            ->createOrganizationForUserWithRole(
                $user,
                'super-admin',
            );

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->asTenant(
                $token,
                $otherOrganization,
            )
            ->postJson(
                '/api/clients',
                [
                    'name' =>
                    'Cliente Outra Organização',

                    'document' =>
                    '12345678901',
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'organization_id',
                $otherOrganization->id,
            );

        $this->assertDatabaseHas(
            'clients',
            [
                'organization_id' =>
                $this->organization->id,

                'document' =>
                '12345678901',
            ],
        );

        $this->assertDatabaseHas(
            'clients',
            [
                'organization_id' =>
                $otherOrganization->id,

                'document' =>
                '12345678901',
            ],
        );

        $this->assertSame(
            2,
            Client::withoutGlobalScopes()
                ->where(
                    'document',
                    '12345678901',
                )
                ->count(),
        );
    }

    public function test_marital_status_deve_existir(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                '/api/clients',
                [
                    'name' =>
                    'Cliente Teste',

                    'document' =>
                    '12345678901',

                    'marital_status_id' =>
                    999999,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'marital_status_id',
            ]);
    }

    public function test_exibe_cliente_com_estado_civil(): void
    {
        $maritalStatus =
            MaritalStatus::firstOrFail();

        $client =
            Client::factory()
            ->for(
                $this->organization
            )
            ->create([
                'marital_status_id' =>
                $maritalStatus->id,
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/clients/{$client->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'marital_status.id',
                $maritalStatus->id,
            );
    }

    public function test_nao_exibe_cliente_de_outra_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $client =
            Client::factory()
            ->for(
                $otherOrganization
            )
            ->create();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/clients/{$client->id}"
            )
            ->assertNotFound();
    }

    public function test_atualiza_cliente(): void
    {
        $client =
            Client::factory()
            ->for(
                $this->organization
            )
            ->create([
                'name' =>
                'Cliente Antigo',

                'document' =>
                '12345678901',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/clients/{$client->id}",
                [
                    'name' =>
                    'Cliente Atualizado',

                    'document' =>
                    '12345678901',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'name',
                'Cliente Atualizado',
            );

        $this->assertDatabaseHas(
            'clients',
            [
                'id' =>
                $client->id,

                'organization_id' =>
                $this->organization->id,

                'name' =>
                'Cliente Atualizado',
            ],
        );
    }

    public function test_nao_atualiza_cliente_de_outra_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $client =
            Client::factory()
            ->for(
                $otherOrganization
            )
            ->create([
                'name' =>
                'Cliente Original',
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/clients/{$client->id}",
                [
                    'name' =>
                    'Tentativa de alteração',

                    'document' =>
                    $client->document,
                ],
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'clients',
            [
                'id' =>
                $client->id,

                'name' =>
                'Cliente Original',
            ],
        );
    }

    public function test_exclui_cliente(): void
    {
        $client =
            Client::factory()
            ->for(
                $this->organization
            )
            ->create();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->deleteJson(
                "/api/clients/{$client->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'clients',
            [
                'id' =>
                $client->id,
            ],
        );
    }

    public function test_nao_exclui_cliente_de_outra_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()
            ->create();

        $client =
            Client::factory()
            ->for(
                $otherOrganization
            )
            ->create();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->deleteJson(
                "/api/clients/{$client->id}"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'clients',
            [
                'id' =>
                $client->id,
            ],
        );
    }

    private function createOrganizationForUserWithRole(
        User $user,
        string $roleName,
    ): Organization {
        $organization =
            Organization::factory()
            ->create();

        $this->attachUser(
            $user,
            $organization,
        );

        $sourceRole =
            Role::query()
            ->where(
                'organization_id',
                $this->organization->id,
            )
            ->where(
                'name',
                $roleName,
            )
            ->where(
                'guard_name',
                'api',
            )
            ->with('permissions')
            ->firstOrFail();

        $role =
            Role::query()
            ->create([
                'organization_id' =>
                $organization->id,

                'name' =>
                $sourceRole->name,

                'guard_name' =>
                $sourceRole->guard_name,

                'description' =>
                $sourceRole->description,
            ]);

        $role->syncPermissions(
            $sourceRole->permissions
        );

        $this->assignRole(
            $user,
            $organization,
            $role,
        );

        return $organization;
    }

    private function assignRole(
        User $user,
        Organization $organization,
        Role $role,
    ): void {
        $previousTeamId =
            getPermissionsTeamId();

        try {
            setPermissionsTeamId(
                $organization->id
            );

            $this
                ->clearPermissionRelations(
                    $user
                );

            $user->syncRoles([
                $role,
            ]);
        } finally {
            setPermissionsTeamId(
                $previousTeamId
            );

            $this
                ->clearPermissionRelations(
                    $user
                );
        }
    }

    private function clearPermissionRelations(
        User $user,
    ): void {
        $user->unsetRelation(
            'roles'
        );

        $user->unsetRelation(
            'permissions'
        );
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

    private function loginAsSuperAdmin(): string
    {
        return auth('api')->login(
            $this->superAdmin()
        );
    }

    private function attachUser(
        User $user,
        Organization $organization,
    ): void {
        $organization
            ->users()
            ->syncWithoutDetaching([
                $user->id => [
                    'status' =>
                    'active',
                ],
            ]);
    }

    private function asTenant(
        string $token,
        Organization $organization,
    ): static {
        return $this
            ->withToken($token)
            ->withHeader(
                'X-Tenant',
                $organization->slug,
            );
    }
}
