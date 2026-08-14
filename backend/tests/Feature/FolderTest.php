<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_index_exige_autenticacao(): void
    {
        $this
            ->getJson('/api/folders')
            ->assertUnauthorized();
    }

    public function test_usuario_sem_permissao_nao_pode_listar_pastas(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->getJson('/api/folders')
            ->assertForbidden();
    }

    public function test_usuario_com_permissao_pode_listar_pastas(): void
    {
        $user = User::factory()->create();

        $user->givePermissionTo(
            'folders.view'
        );

        Folder::query()->create([
            'name' => 'Ação indenizatória',
            'process_number' =>
            '5000000-00.2026.8.21.0001',
        ]);

        $token = auth('api')->login($user);

        $this
            ->withToken($token)
            ->getJson('/api/folders')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Ação indenizatória',
            ]);
    }

    public function test_cria_pasta(): void
    {
        $token = $this->loginAsSuperAdmin();

        $response = $this
            ->withToken($token)
            ->postJson('/api/folders', [
                'name' => 'Ação indenizatória',
                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'name',
                'Ação indenizatória'
            )
            ->assertJsonPath(
                'process_number',
                '5000000-00.2026.8.21.0001'
            );

        $this->assertDatabaseHas(
            'folders',
            [
                'name' =>
                'Ação indenizatória',
                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]
        );
    }

    public function test_nome_e_obrigatorio(): void
    {
        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->postJson('/api/folders', [
                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    public function test_process_number_pode_ser_null(): void
    {
        $token = $this->loginAsSuperAdmin();

        $response = $this
            ->withToken($token)
            ->postJson('/api/folders', [
                'name' =>
                'Atendimento extrajudicial',
                'process_number' => null,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'process_number',
                null
            );

        $this->assertDatabaseHas(
            'folders',
            [
                'name' =>
                'Atendimento extrajudicial',
                'process_number' => null,
            ]
        );
    }

    public function test_exibe_pasta(): void
    {
        $folder = Folder::query()->create([
            'name' => 'Ação de cobrança',
            'process_number' =>
            '5000001-00.2026.8.21.0001',
        ]);

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->getJson(
                "/api/folders/{$folder->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'id',
                $folder->id
            )
            ->assertJsonPath(
                'name',
                'Ação de cobrança'
            );
    }

    public function test_atualiza_pasta(): void
    {
        $folder = Folder::query()->create([
            'name' => 'Nome original',
            'process_number' => null,
        ]);

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->patchJson(
                "/api/folders/{$folder->id}",
                [
                    'name' =>
                    'Nome atualizado',
                    'process_number' =>
                    '5000002-00.2026.8.21.0001',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'name',
                'Nome atualizado'
            );

        $this->assertDatabaseHas(
            'folders',
            [
                'id' => $folder->id,
                'name' =>
                'Nome atualizado',
            ]
        );
    }

    public function test_exclui_pasta(): void
    {
        $folder = Folder::query()->create([
            'name' => 'Pasta para excluir',
            'process_number' => null,
        ]);

        $token = $this->loginAsSuperAdmin();

        $this
            ->withToken($token)
            ->deleteJson(
                "/api/folders/{$folder->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folders',
            [
                'id' => $folder->id,
            ]
        );
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
