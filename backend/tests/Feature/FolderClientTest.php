<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Folder;
use App\Models\FolderClient;
use App\Models\Qualification;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_store_exige_autenticacao(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();
        $qualification = $this->qualification();

        $this
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' => $client->id,
                    'qualification_id' =>
                    $qualification->id,
                ]
            )
            ->assertUnauthorized();
    }

    public function test_usuario_sem_permissao_nao_pode_adicionar_cliente(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();
        $qualification = $this->qualification();

        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' => $client->id,
                    'qualification_id' =>
                    $qualification->id,
                ]
            )
            ->assertForbidden();
    }

    public function test_adiciona_cliente_a_pasta(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();
        $qualification = $this->qualification();

        $token = $this->loginAsSuperAdmin();

        $response = $this
            ->withToken($token)
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' => $client->id,
                    'qualification_id' =>
                    $qualification->id,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'folder_id',
                $folder->id
            )
            ->assertJsonPath(
                'client.id',
                $client->id
            )
            ->assertJsonPath(
                'qualification.id',
                $qualification->id
            );

        $this->assertDatabaseHas(
            'folder_clients',
            [
                'folder_id' => $folder->id,
                'client_id' => $client->id,
                'qualification_id' =>
                $qualification->id,
            ]
        );
    }

    public function test_client_id_deve_existir(): void
    {
        $folder = $this->createFolder();
        $qualification = $this->qualification();

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' => 999999,
                    'qualification_id' =>
                    $qualification->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'client_id',
            ]);
    }

    public function test_qualification_id_deve_existir(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' => $client->id,
                    'qualification_id' => 999999,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'qualification_id',
            ]);
    }

    public function test_nao_permite_vinculo_exatamente_duplicado(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();
        $qualification = $this->qualification();

        FolderClient::query()->create([
            'folder_id' => $folder->id,
            'client_id' => $client->id,
            'qualification_id' =>
            $qualification->id,
        ]);

        $token = $this->loginAsSuperAdmin();

        $response = $this
            ->withToken($token)
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' => $client->id,
                    'qualification_id' =>
                    $qualification->id,
                ]
            );

        $response->assertStatus(422);
    }

    public function test_mesmo_cliente_pode_ter_qualificacoes_diferentes_na_mesma_pasta(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();

        $author = Qualification::query()
            ->where('name', 'Autor')
            ->firstOrFail();

        $interested = Qualification::query()
            ->where('name', 'Interessado')
            ->firstOrFail();

        FolderClient::query()->create([
            'folder_id' => $folder->id,
            'client_id' => $client->id,
            'qualification_id' =>
            $author->id,
        ]);

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->postJson(
                "/api/folders/{$folder->id}/clients",
                [
                    'client_id' => $client->id,
                    'qualification_id' =>
                    $interested->id,
                ]
            )
            ->assertCreated();

        $this->assertDatabaseCount(
            'folder_clients',
            2
        );
    }

    public function test_atualiza_qualificacao_do_cliente(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();

        $author = Qualification::query()
            ->where('name', 'Autor')
            ->firstOrFail();

        $interested = Qualification::query()
            ->where('name', 'Interessado')
            ->firstOrFail();

        $folderClient =
            FolderClient::query()->create([
                'folder_id' => $folder->id,
                'client_id' => $client->id,
                'qualification_id' =>
                $author->id,
            ]);

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->patchJson(
                "/api/folders/{$folder->id}/clients/{$folderClient->id}",
                [
                    'qualification_id' =>
                    $interested->id,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'qualification.id',
                $interested->id
            );

        $this->assertDatabaseHas(
            'folder_clients',
            [
                'id' => $folderClient->id,
                'qualification_id' =>
                $interested->id,
            ]
        );
    }

    public function test_nao_atualiza_vinculo_de_outra_pasta(): void
    {
        $folderA = $this->createFolder(
            'Pasta A'
        );

        $folderB = $this->createFolder(
            'Pasta B'
        );

        $client = $this->createClient();

        $qualification = $this->qualification();

        $folderClient =
            FolderClient::query()->create([
                'folder_id' => $folderB->id,
                'client_id' => $client->id,
                'qualification_id' =>
                $qualification->id,
            ]);

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->patchJson(
                "/api/folders/{$folderA->id}/clients/{$folderClient->id}",
                [
                    'qualification_id' =>
                    $qualification->id,
                ]
            )
            ->assertNotFound();
    }

    public function test_remove_cliente_da_pasta(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();
        $qualification = $this->qualification();

        $folderClient =
            FolderClient::query()->create([
                'folder_id' => $folder->id,
                'client_id' => $client->id,
                'qualification_id' =>
                $qualification->id,
            ]);

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->deleteJson(
                "/api/folders/{$folder->id}/clients/{$folderClient->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folder_clients',
            [
                'id' => $folderClient->id,
            ]
        );
    }

    public function test_nao_remove_vinculo_de_outra_pasta(): void
    {
        $folderA = $this->createFolder(
            'Pasta A'
        );

        $folderB = $this->createFolder(
            'Pasta B'
        );

        $client = $this->createClient();

        $qualification = $this->qualification();

        $folderClient =
            FolderClient::query()->create([
                'folder_id' => $folderB->id,
                'client_id' => $client->id,
                'qualification_id' =>
                $qualification->id,
            ]);

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->deleteJson(
                "/api/folders/{$folderA->id}/clients/{$folderClient->id}"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'folder_clients',
            [
                'id' => $folderClient->id,
            ]
        );
    }

    public function test_show_da_pasta_retorna_clientes_e_qualificacoes(): void
    {
        $folder = $this->createFolder();
        $client = $this->createClient();
        $qualification = $this->qualification();

        FolderClient::query()->create([
            'folder_id' => $folder->id,
            'client_id' => $client->id,
            'qualification_id' =>
            $qualification->id,
        ]);

        $token = $this->loginAsSuperAdmin();

        $response = $this
            ->withToken($token)
            ->getJson(
                "/api/folders/{$folder->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'folder_clients.0.client.id',
                $client->id
            )
            ->assertJsonPath(
                'folder_clients.0.qualification.id',
                $qualification->id
            );
    }

    private function createFolder(
        string $name = 'Pasta teste'
    ): Folder {
        return Folder::query()->create([
            'name' => $name,
            'process_number' => null,
        ]);
    }

    private function createClient(): Client
    {
        return Client::query()->create([
            'name' => 'Cliente Teste',
            'document' => fake()
                ->unique()
                ->numerify('###########'),
        ]);
    }

    private function qualification(): Qualification
    {
        return Qualification::query()
            ->where('name', 'Autor')
            ->firstOrFail();
    }

    private function loginAsSuperAdmin(): string
    {
        $response = $this->postJson(
            '/api/auth/login',
            [
                'email' =>
                'super-admin@legalis.local',
                'password' => 'l3g@l1s',
            ]
        );

        $response->assertOk();

        return $response->json(
            'access_token'
        );
    }
}
