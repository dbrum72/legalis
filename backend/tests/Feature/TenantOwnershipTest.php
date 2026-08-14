<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Folder;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class TenantOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizacao_possui_clientes(): void
    {
        $organization =
            Organization::factory()->create();

        Client::factory()
            ->count(2)
            ->for($organization)
            ->create();

        $this->assertCount(
            2,
            $organization
                ->clients()
                ->get(),
        );
    }

    public function test_cliente_pertence_a_organizacao(): void
    {
        $organization =
            Organization::factory()->create();

        $client =
            Client::factory()
            ->for($organization)
            ->create();

        $this->assertTrue(
            $client
                ->organization
                ->is($organization),
        );
    }

    public function test_organizacao_possui_pastas(): void
    {
        $organization =
            Organization::factory()->create();

        Folder::factory()
            ->count(2)
            ->for($organization)
            ->create();

        $this->assertCount(
            2,
            $organization
                ->folders()
                ->get(),
        );
    }

    public function test_pasta_pertence_a_organizacao(): void
    {
        $organization =
            Organization::factory()->create();

        $folder =
            Folder::factory()
            ->for($organization)
            ->create();

        $this->assertTrue(
            $folder
                ->organization
                ->is($organization),
        );
    }

    public function test_cliente_exige_organizacao(): void
    {
        $this->expectException(
            QueryException::class,
        );

        Client::query()->create([
            'name' =>
            'Cliente sem organização',

            'document' =>
            '12345678901',
        ]);
    }

    public function test_pasta_exige_organizacao(): void
    {
        $this->expectException(
            QueryException::class,
        );

        Folder::query()->create([
            'name' =>
            'Pasta sem organização',
        ]);
    }

    public function test_documento_e_unico_dentro_da_mesma_organizacao(): void
    {
        $organization =
            Organization::factory()->create();

        Client::factory()
            ->for($organization)
            ->create([
                'document' =>
                '12345678901',
            ]);

        $this->expectException(
            QueryException::class,
        );

        Client::factory()
            ->for($organization)
            ->create([
                'document' =>
                '12345678901',
            ]);
    }

    public function test_mesmo_documento_pode_existir_em_organizacoes_diferentes(): void
    {
        $organizationA =
            Organization::factory()->create();

        $organizationB =
            Organization::factory()->create();

        $clientA =
            Client::factory()
            ->for($organizationA)
            ->create([
                'document' =>
                '12345678901',
            ]);

        $clientB =
            Client::factory()
            ->for($organizationB)
            ->create([
                'document' =>
                '12345678901',
            ]);

        $this->assertNotSame(
            $clientA->id,
            $clientB->id,
        );

        $this->assertSame(
            $organizationA->id,
            $clientA->organization_id,
        );

        $this->assertSame(
            $organizationB->id,
            $clientB->organization_id,
        );
    }

    public function test_clientes_de_organizacoes_diferentes_ficam_separados_pela_relacao(): void
    {
        $organizationA =
            Organization::factory()->create();

        $organizationB =
            Organization::factory()->create();

        Client::factory()
            ->for($organizationA)
            ->create([
                'name' =>
                'Cliente Organização A',
            ]);

        Client::factory()
            ->for($organizationB)
            ->create([
                'name' =>
                'Cliente Organização B',
            ]);

        $clientsA =
            $organizationA
            ->clients()
            ->pluck('name');

        $this->assertTrue(
            $clientsA->contains(
                'Cliente Organização A',
            ),
        );

        $this->assertFalse(
            $clientsA->contains(
                'Cliente Organização B',
            ),
        );
    }

    public function test_pastas_de_organizacoes_diferentes_ficam_separadas_pela_relacao(): void
    {
        $organizationA =
            Organization::factory()->create();

        $organizationB =
            Organization::factory()->create();

        Folder::factory()
            ->for($organizationA)
            ->create([
                'name' =>
                'Pasta Organização A',
            ]);

        Folder::factory()
            ->for($organizationB)
            ->create([
                'name' =>
                'Pasta Organização B',
            ]);

        $foldersA =
            $organizationA
            ->folders()
            ->pluck('name');

        $this->assertTrue(
            $foldersA->contains(
                'Pasta Organização A',
            ),
        );

        $this->assertFalse(
            $foldersA->contains(
                'Pasta Organização B',
            ),
        );
    }
}
