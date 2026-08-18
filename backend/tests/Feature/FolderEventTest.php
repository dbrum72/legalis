<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderEventTest extends TestCase
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
                'Pasta com eventos',

                'process_number' =>
                '5000000-00.2026.8.21.0001',
            ]);
    }

    public function test_listagem_de_eventos_exige_autenticacao(): void
    {
        $this
            ->withHeader(
                'X-Tenant',
                $this->organization->slug,
            )
            ->getJson(
                $this->eventsUrl()
            )
            ->assertUnauthorized();
    }

    public function test_listagem_de_eventos_exige_tenant(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->withToken(
                $token
            )
            ->getJson(
                $this->eventsUrl()
            )
            ->assertBadRequest();
    }

    public function test_cria_evento_na_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->eventsUrl(),
                [
                    'type' =>
                    'hearing',

                    'title' =>
                    'Audiência de instrução',

                    'description' =>
                    'Audiência de instrução e julgamento.',

                    'starts_at' =>
                    '2026-09-10 14:00:00',

                    'ends_at' =>
                    '2026-09-10 15:30:00',

                    'location' =>
                    '3ª Vara Cível de Pelotas',
                ],
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'folder_id',
                $this->folder->id,
            )
            ->assertJsonPath(
                'type',
                'hearing',
            )
            ->assertJsonPath(
                'title',
                'Audiência de instrução',
            )
            ->assertJsonPath(
                'description',
                'Audiência de instrução e julgamento.',
            )
            ->assertJsonPath(
                'location',
                '3ª Vara Cível de Pelotas',
            )
            ->assertJsonPath(
                'status',
                'scheduled',
            )
            ->assertJsonPath(
                'completed_at',
                null,
            )
            ->assertJsonPath(
                'user.name',
                'Super Admin',
            )
            ->assertJsonStructure([
                'id',
                'folder_id',
                'user_id',
                'type',
                'title',
                'description',
                'starts_at',
                'ends_at',
                'location',
                'status',
                'completed_at',
                'created_at',
                'updated_at',

                'user' => [
                    'id',
                    'name',
                ],
            ]);

        $this->assertDatabaseHas(
            'folder_events',
            [
                'folder_id' =>
                $this->folder->id,

                'type' =>
                'hearing',

                'title' =>
                'Audiência de instrução',

                'status' =>
                'scheduled',
            ],
        );
    }

    public function test_evento_exige_tipo_titulo_e_inicio(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->eventsUrl(),
                [
                    'type' =>
                    '',

                    'title' =>
                    '',

                    'starts_at' =>
                    null,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'type',
                'title',
                'starts_at',
            ]);
    }

    public function test_lista_eventos_em_ordem_cronologica(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->createEvent(
                $token,
                [
                    'type' =>
                    'meeting',

                    'title' =>
                    'Reunião posterior',

                    'starts_at' =>
                    '2026-09-20 14:00:00',
                ],
            )
            ->assertCreated();

        $this
            ->createEvent(
                $token,
                [
                    'type' =>
                    'hearing',

                    'title' =>
                    'Audiência próxima',

                    'starts_at' =>
                    '2026-09-10 10:00:00',
                ],
            )
            ->assertCreated();

        $this
            ->createEvent(
                $token,
                [
                    'type' =>
                    'diligence',

                    'title' =>
                    'Diligência intermediária',

                    'starts_at' =>
                    '2026-09-15 09:00:00',
                ],
            )
            ->assertCreated();

        $response =
            $this
            ->asTenant(
                $token,
            )
            ->getJson(
                $this->eventsUrl()
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                3
            )
            ->assertJsonPath(
                '0.title',
                'Audiência próxima',
            )
            ->assertJsonPath(
                '1.title',
                'Diligência intermediária',
            )
            ->assertJsonPath(
                '2.title',
                'Reunião posterior',
            );
    }

    public function test_conclui_evento(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $event =
            $this
            ->createEvent(
                $token,
                [
                    'type' =>
                    'hearing',

                    'title' =>
                    'Audiência realizada',

                    'starts_at' =>
                    '2026-09-10 14:00:00',
                ],
            )
            ->assertCreated();

        $eventId =
            $event->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->patchJson(
                $this->completeUrl(
                    $eventId
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'status',
                'completed',
            )
            ->assertJsonPath(
                'completed_at',
                fn($value) =>
                $value !== null
            );

        $this->assertDatabaseHas(
            'folder_events',
            [
                'id' =>
                $eventId,

                'status' =>
                'completed',
            ],
        );
    }

    public function test_exclui_evento(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $event =
            $this
            ->createEvent(
                $token,
                [
                    'type' =>
                    'meeting',

                    'title' =>
                    'Reunião temporária',

                    'starts_at' =>
                    '2026-09-12 10:00:00',
                ],
            )
            ->assertCreated();

        $eventId =
            $event->json(
                'id'
            );

        $this
            ->asTenant(
                $token,
            )
            ->deleteJson(
                $this->eventUrl(
                    $eventId
                )
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'folder_events',
            [
                'id' =>
                $eventId,
            ],
        );
    }

    public function test_eventos_ficam_isolados_por_organizacao(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $this
            ->createEvent(
                $token,
                [
                    'type' =>
                    'hearing',

                    'title' =>
                    'Evento interno',

                    'starts_at' =>
                    '2026-09-10 14:00:00',
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

        $this
            ->asTenant(
                $token,
            )
            ->getJson(
                "/api/folders/{$otherFolder->id}/events"
            )
            ->assertNotFound();
    }

    public function test_nao_conclui_evento_de_outra_pasta(): void
    {
        $token =
            $this->loginAsSuperAdmin();

        $event =
            $this
            ->createEvent(
                $token,
                [
                    'type' =>
                    'hearing',

                    'title' =>
                    'Evento da pasta principal',

                    'starts_at' =>
                    '2026-09-10 14:00:00',
                ],
            )
            ->assertCreated();

        $eventId =
            $event->json(
                'id'
            );

        $otherFolder =
            $this->organization
            ->folders()
            ->create([
                'name' =>
                'Segunda pasta',

                'process_number' =>
                null,
            ]);

        $this
            ->asTenant(
                $token,
            )
            ->patchJson(
                "/api/folders/{$otherFolder->id}/events/{$eventId}/complete"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'folder_events',
            [
                'id' =>
                $eventId,

                'status' =>
                'scheduled',
            ],
        );
    }

    private function createEvent(
        string $token,
        array $payload,
    ) {
        return $this
            ->asTenant(
                $token,
            )
            ->postJson(
                $this->eventsUrl(),
                $payload,
            );
    }

    private function eventsUrl(): string
    {
        return "/api/folders/{$this->folder->id}/events";
    }

    private function eventUrl(
        int|string $eventId,
    ): string {
        return "{$this->eventsUrl()}/{$eventId}";
    }

    private function completeUrl(
        int|string $eventId,
    ): string {
        return "{$this->eventUrl($eventId)}/complete";
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
