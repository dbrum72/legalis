<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\LegalPublication;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPublicationTest extends TestCase
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

    public function test_lista_apenas_publicacoes_da_organizacao_atual(): void
    {
        $this->createPublication(
            $this->organization,
            'Publicação do escritório atual',
        );

        $otherOrganization =
            Organization::factory()
                ->create();

        $this->createPublication(
            $otherOrganization,
            'Publicação de outro escritório',
        );

        $this
            ->asTenant(
                $this->login(
                    'advogado-pleno@legalis.local'
                )
            )
            ->getJson(
                '/api/legal-publications'
            )
            ->assertOk()
            ->assertJsonFragment([
                'content' => 'Publicação do escritório atual',
            ])
            ->assertJsonMissing([
                'content' => 'Publicação de outro escritório',
            ]);
    }

    public function test_filtro_retorna_caixa_de_publicacoes_nao_vinculadas(): void
    {
        $folder =
            Folder::factory()
                ->for($this->organization)
                ->create();

        $this->createPublication(
            $this->organization,
            'Publicação não vinculada',
        );

        $this->createPublication(
            $this->organization,
            'Publicação vinculada',
            $folder,
        );

        $this
            ->asTenant(
                $this->login(
                    'advogado-pleno@legalis.local'
                )
            )
            ->getJson(
                '/api/legal-publications?link_status=unlinked'
            )
            ->assertOk()
            ->assertJsonPath(
                'total',
                1,
            )
            ->assertJsonPath(
                'data.0.content',
                'Publicação não vinculada',
            );
    }

    public function test_usuario_com_permissao_vincula_publicacao_a_pasta(): void
    {
        $publication =
            $this->createPublication(
                $this->organization,
                'Publicação a vincular',
            );

        $folder =
            Folder::factory()
                ->for($this->organization)
                ->create([
                    'name' => 'Pasta vinculada',
                ]);

        $this
            ->asTenant(
                $this->login(
                    'advogado-pleno@legalis.local'
                )
            )
            ->patchJson(
                "/api/legal-publications/{$publication->id}/folder",
                [
                    'folder_id' => $folder->id,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'folder.id',
                $folder->id,
            );

        $this->assertDatabaseHas(
            'legal_publications',
            [
                'id' => $publication->id,

                'folder_id' => $folder->id,
            ],
        );
    }

    public function test_nao_vincula_publicacao_a_pasta_de_outra_organizacao(): void
    {
        $publication =
            $this->createPublication(
                $this->organization,
                'Publicação a vincular',
            );

        $otherOrganization =
            Organization::factory()
                ->create();

        $otherFolder =
            Folder::factory()
                ->for($otherOrganization)
                ->create();

        $this
            ->asTenant(
                $this->login(
                    'advogado-pleno@legalis.local'
                )
            )
            ->patchJson(
                "/api/legal-publications/{$publication->id}/folder",
                [
                    'folder_id' => $otherFolder->id,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'folder_id',
            ]);
    }

    public function test_conferencia_registra_usuario_data_e_resultado(): void
    {
        $publication =
            $this->createPublication(
                $this->organization,
                'Publicação a conferir',
            );

        $user =
            User::query()
                ->where(
                    'email',
                    'advogado-pleno@legalis.local',
                )
                ->firstOrFail();

        $token =
            auth('api')->login(
                $user
            );

        $this
            ->asTenant($token)
            ->patchJson(
                "/api/legal-publications/{$publication->id}/review",
                [
                    'review_status' => LegalPublication::REVIEWED,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'review_status',
                LegalPublication::REVIEWED,
            )
            ->assertJsonPath(
                'reviewer.id',
                $user->id,
            );

        $publication->refresh();

        $this->assertSame(
            $user->id,
            $publication->reviewed_by,
        );

        $this->assertNotNull(
            $publication->reviewed_at
        );
    }

    private function createPublication(
        Organization $organization,
        string $content,
        ?Folder $folder = null,
    ): LegalPublication {
        return $organization
            ->legalPublications()
            ->create([
                'folder_id' => $folder?->id,

                'source' => 'djen',

                'external_id' => fake()->unique()->numerify(
                    '######'
                ),

                'process_number' => '5000000-00.2026.8.21.0001',

                'normalized_process_number' => '50000000020268210001',

                'court_acronym' => 'TJRS',

                'content' => $content,

                'raw_payload' => [
                    'texto' => $content,
                ],

                'payload_hash' => hash(
                    'sha256',
                    $content,
                ),

                'review_status' => LegalPublication::REVIEW_PENDING,

                'available_on' => '2026-08-27',

                'imported_at' => now(),

                'last_seen_at' => now(),
            ]);
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
