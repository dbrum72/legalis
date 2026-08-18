<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FolderDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Folder $folder;

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

        $this->folder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Pasta de teste',

                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);
    }

    public function test_listagem_de_documentos_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                $this->documentsUrl()
            )
            ->assertUnauthorized();
    }

    public function test_listagem_de_documentos_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                $this->documentsUrl()
            )
            ->assertBadRequest();
    }

    public function test_lista_documentos_da_pasta(): void
    {
        Storage::fake(
            'local'
        );

        $token =
            $this->loginAsSuperAdmin();

        $this->uploadDocument(
            $token,
            [
                'file' =>
                UploadedFile::fake()
                    ->create(
                        'peticao-inicial.pdf',
                        120,
                        'application/pdf',
                    ),

                'name' =>
                'Petição inicial',

                'description' =>
                'Petição inicial protocolada.',
            ],
        )
            ->assertCreated();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->getJson(
                $this->documentsUrl()
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1
            )
            ->assertJsonPath(
                '0.name',
                'Petição inicial',
            )
            ->assertJsonPath(
                '0.original_name',
                'peticao-inicial.pdf',
            )
            ->assertJsonPath(
                '0.mime_type',
                'application/pdf',
            )
            ->assertJsonPath(
                '0.description',
                'Petição inicial protocolada.',
            )
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'folder_id',
                    'name',
                    'original_name',
                    'mime_type',
                    'size',
                    'description',
                    'created_at',

                    'user' => [
                        'id',
                        'name',
                    ],
                ],
            ]);
    }

    public function test_anexa_documento_em_storage_privado(): void
    {
        Storage::fake(
            'local'
        );

        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this->uploadDocument(
                $token,
                [
                    'file' =>
                    UploadedFile::fake()
                        ->create(
                            'contrato.pdf',
                            80,
                            'application/pdf',
                        ),

                    'name' =>
                    'Contrato',

                    'description' =>
                    null,
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'name',
                'Contrato',
            )
            ->assertJsonPath(
                'original_name',
                'contrato.pdf',
            )
            ->assertJsonPath(
                'folder_id',
                $this->folder->id,
            );

        $files =
            Storage::disk(
                'local'
            )
            ->allFiles(
                $this->storageDirectory()
            );

        $this->assertCount(
            1,
            $files
        );

        Storage::disk(
            'public'
        )
            ->assertMissing(
                $files[0]
            );
    }

    public function test_download_retorna_documento_privado(): void
    {
        Storage::fake(
            'local'
        );

        $token =
            $this->loginAsSuperAdmin();

        $uploadResponse =
            $this->uploadDocument(
                $token,
                [
                    'file' =>
                    UploadedFile::fake()
                        ->create(
                            'procuracao.pdf',
                            50,
                            'application/pdf',
                        ),

                    'name' =>
                    'Procuração',
                ],
            );

        $uploadResponse
            ->assertCreated();

        $documentId =
            $uploadResponse->json(
                'id'
            );

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->get(
                $this->downloadUrl(
                    $documentId
                )
            );

        $response
            ->assertOk();

        $this->assertStringContainsString(
            'procuracao.pdf',
            (string) $response
                ->headers
                ->get(
                    'content-disposition'
                ),
        );
    }

    public function test_exclui_documento_e_arquivo_fisico(): void
    {
        Storage::fake(
            'local'
        );

        $token =
            $this->loginAsSuperAdmin();

        $uploadResponse =
            $this->uploadDocument(
                $token,
                [
                    'file' =>
                    UploadedFile::fake()
                        ->create(
                            'documento.pdf',
                            50,
                            'application/pdf',
                        ),

                    'name' =>
                    'Documento temporário',
                ],
            );

        $uploadResponse
            ->assertCreated();

        $documentId =
            $uploadResponse->json(
                'id'
            );

        $filesBeforeDelete =
            Storage::disk(
                'local'
            )
            ->allFiles(
                $this->storageDirectory()
            );

        $this->assertCount(
            1,
            $filesBeforeDelete
        );

        $this
            ->asTenant(
                $token,
            )
            ->deleteJson(
                $this->documentUrl(
                    $documentId
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folder_documents',
            [
                'id' =>
                $documentId,
            ],
        );

        $filesAfterDelete =
            Storage::disk(
                'local'
            )
            ->allFiles(
                $this->storageDirectory()
            );

        $this->assertCount(
            0,
            $filesAfterDelete
        );
    }

    public function test_documentos_ficam_isolados_por_organizacao(): void
    {
        Storage::fake(
            'local'
        );

        $token =
            $this->loginAsSuperAdmin();

        $this->uploadDocument(
            $token,
            [
                'file' =>
                UploadedFile::fake()
                    ->create(
                        'documento-interno.pdf',
                        40,
                        'application/pdf',
                    ),

                'name' =>
                'Documento interno',
            ],
        )
            ->assertCreated();

        $otherOrganization =
            Organization::factory()
            ->create();

        $otherFolder =
            $otherOrganization
            ->folders()
            ->create([
                'name' =>
                'Outra pasta',

                'process_number' =>
                null,
            ]);

        /*
         * A pasta de outra organização não pode
         * ser acessada através do tenant atual.
         */
        $this
            ->asTenant(
                $token,
            )
            ->getJson(
                "/api/folders/{$otherFolder->id}/documents"
            )
            ->assertNotFound();
    }

    private function uploadDocument(
        string $token,
        array $payload,
    ) {
        return $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->documentsUrl(),
                $payload,
            );
    }

    private function documentsUrl(): string
    {
        return "/api/folders/{$this->folder->id}/documents";
    }

    private function documentUrl(
        int|string $documentId,
    ): string {
        return "{$this->documentsUrl()}/{$documentId}";
    }

    private function downloadUrl(
        int|string $documentId,
    ): string {
        return "{$this->documentUrl($documentId)}/download";
    }

    private function storageDirectory(): string
    {
        return implode(
            '/',
            [
                'organizations',
                $this->organization->id,
                'folders',
                $this->folder->id,
                'documents',
            ],
        );
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

        return auth(
            'api'
        )->login(
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
