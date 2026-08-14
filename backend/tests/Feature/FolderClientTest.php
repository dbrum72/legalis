<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Folder;
use App\Models\FolderClient;
use App\Models\Organization;
use App\Models\Qualification;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderClientTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Qualification $qualification;

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

        $this->qualification =
            Qualification::query()
            ->firstOrFail();
    }

    public function test_store_exige_autenticacao(): void
    {
        $folder =
            $this->createFolder();

        $client =
            $this->createClient();

        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    $client->id,

                    'qualification_id' =>
                    $this->qualification->id,
                ],
            )
            ->assertUnauthorized();
    }

    public function test_usuario_sem_permissao_nao_pode_adicionar_cliente(): void
    {
        $folder =
            $this->createFolder();

        $client =
            $this->createClient();

        $user =
            User::factory()->create();

        $this->attachUser(
            $user,
            $this->organization,
        );

        $token = auth('api')->login(
            $user
        );

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    $client->id,

                    'qualification_id' =>
                    $this->qualification->id,
                ],
            )
            ->assertForbidden();
    }

    public function test_adiciona_cliente_a_pasta(): void
    {
        $folder =
            $this->createFolder();

        $client =
            $this->createClient();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    $client->id,

                    'qualification_id' =>
                    $this->qualification->id,
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'client.id',
                $client->id,
            )
            ->assertJsonPath(
                'qualification.id',
                $this->qualification->id,
            );

        $this->assertDatabaseHas(
            'folder_clients',
            [
                'folder_id' =>
                $folder->id,

                'client_id' =>
                $client->id,

                'qualification_id' =>
                $this->qualification->id,
            ],
        );
    }

    public function test_client_id_deve_existir(): void
    {
        $folder =
            $this->createFolder();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    999999,

                    'qualification_id' =>
                    $this->qualification->id,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'client_id',
            ]);
    }

    public function test_client_id_deve_pertencer_a_organizacao_atual(): void
    {
        $folder =
            $this->createFolder();

        $otherOrganization =
            Organization::factory()->create();

        $client =
            Client::factory()
            ->for($otherOrganization)
            ->create();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    $client->id,

                    'qualification_id' =>
                    $this->qualification->id,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'client_id',
            ]);

        $this->assertDatabaseMissing(
            'folder_clients',
            [
                'folder_id' =>
                $folder->id,

                'client_id' =>
                $client->id,
            ],
        );
    }

    public function test_qualification_id_deve_existir(): void
    {
        $folder =
            $this->createFolder();

        $client =
            $this->createClient();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    $client->id,

                    'qualification_id' =>
                    999999,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'qualification_id',
            ]);
    }

    public function test_nao_permite_vinculo_exatamente_duplicado(): void
    {
        $folder =
            $this->createFolder();

        $client =
            $this->createClient();

        $folder
            ->folderClients()
            ->create([
                'client_id' =>
                $client->id,

                'qualification_id' =>
                $this->qualification->id,
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    $client->id,

                    'qualification_id' =>
                    $this->qualification->id,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'client_id',
            ]);
    }

    public function test_mesmo_cliente_pode_ter_qualificacoes_diferentes_na_mesma_pasta(): void
    {
        $folder =
            $this->createFolder();

        $client =
            $this->createClient();

        $otherQualification =
            Qualification::query()
            ->whereKeyNot(
                $this->qualification->id
            )
            ->firstOrFail();

        $folder
            ->folderClients()
            ->create([
                'client_id' =>
                $client->id,

                'qualification_id' =>
                $this->qualification->id,
            ]);

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    $client->id,

                    'qualification_id' =>
                    $otherQualification->id,
                ],
            )
            ->assertCreated();

        $this->assertDatabaseHas(
            'folder_clients',
            [
                'folder_id' =>
                $folder->id,

                'client_id' =>
                $client->id,

                'qualification_id' =>
                $otherQualification->id,
            ],
        );
    }

    public function test_nao_adiciona_cliente_em_pasta_de_outra_organizacao(): void
    {
        $otherOrganization =
            Organization::factory()->create();

        $folder =
            Folder::factory()
            ->for($otherOrganization)
            ->create();

        $client =
            $this->createClient();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' =>
                    $client->id,

                    'qualification_id' =>
                    $this->qualification->id,
                ],
            )
            ->assertNotFound();
    }

    public function test_atualiza_qualificacao_do_cliente(): void
    {
        $folderClient =
            $this->createFolderClient();

        $otherQualification =
            Qualification::query()
            ->whereKeyNot(
                $this->qualification->id
            )
            ->firstOrFail();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/folders/{$folderClient->folder_id}/clients/{$folderClient->id}",
                [
                    'qualification_id' =>
                    $otherQualification->id,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'qualification.id',
                $otherQualification->id,
            );
    }

    public function test_nao_atualiza_vinculo_de_outra_pasta(): void
    {
        $folderClient =
            $this->createFolderClient();

        $otherFolder =
            $this->createFolder();

        $otherQualification =
            Qualification::query()
            ->whereKeyNot(
                $this->qualification->id
            )
            ->firstOrFail();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->patchJson(
                "/api/folders/{$otherFolder->id}/clients/{$folderClient->id}",
                [
                    'qualification_id' =>
                    $otherQualification->id,
                ],
            )
            ->assertNotFound();
    }

    public function test_remove_cliente_da_pasta(): void
    {
        $folderClient =
            $this->createFolderClient();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->deleteJson(
                "/api/folders/{$folderClient->folder_id}/clients/{$folderClient->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folder_clients',
            [
                'id' =>
                $folderClient->id,
            ],
        );
    }

    public function test_nao_remove_vinculo_de_outra_pasta(): void
    {
        $folderClient =
            $this->createFolderClient();

        $otherFolder =
            $this->createFolder();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->deleteJson(
                "/api/folders/{$otherFolder->id}/clients/{$folderClient->id}"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'folder_clients',
            [
                'id' =>
                $folderClient->id,
            ],
        );
    }

    public function test_show_da_pasta_retorna_clientes_e_qualificacoes(): void
    {
        $folderClient =
            $this->createFolderClient();

        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
                $this->organization,
            )
            ->getJson(
                "/api/folders/{$folderClient->folder_id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'folder_clients.0.client.id',
                $folderClient->client_id,
            )
            ->assertJsonPath(
                'folder_clients.0.qualification.id',
                $folderClient->qualification_id,
            );
    }

    private function createFolder(): Folder
    {
        return Folder::factory()
            ->for($this->organization)
            ->create();
    }

    private function createClient(): Client
    {
        return Client::factory()
            ->for($this->organization)
            ->create();
    }

    private function createFolderClient(): FolderClient
    {
        $folder =
            $this->createFolder();

        $client =
            $this->createClient();

        return $folder
            ->folderClients()
            ->create([
                'client_id' =>
                $client->id,

                'qualification_id' =>
                $this->qualification->id,
            ]);
    }

    private function loginAsSuperAdmin(): string
    {
        $user = User::query()
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
