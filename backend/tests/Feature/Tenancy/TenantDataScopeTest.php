<?php

namespace Tests\Feature\Tenancy;

use App\Models\Client;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class TenantDataScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_query_e_filtrada_pela_organizacao_atual(): void
    {
        [$organizationA, $organizationB] =
            $this->organizations();

        Client::factory()
            ->for($organizationA)
            ->create([
                'name' =>
                'Cliente A',
            ]);

        Client::factory()
            ->for($organizationB)
            ->create([
                'name' =>
                'Cliente B',
            ]);

        $this->runAsOrganization(
            $organizationA,
            function (): void {
                $clients =
                    Client::query()
                    ->pluck('name');

                $this->assertSame(
                    ['Cliente A'],
                    $clients->all(),
                );
            }
        );
    }

    public function test_folder_query_e_filtrada_pela_organizacao_atual(): void
    {
        [$organizationA, $organizationB] =
            $this->organizations();

        Folder::factory()
            ->for($organizationA)
            ->create([
                'name' =>
                'Pasta A',
            ]);

        Folder::factory()
            ->for($organizationB)
            ->create([
                'name' =>
                'Pasta B',
            ]);

        $this->runAsOrganization(
            $organizationB,
            function (): void {
                $folders =
                    Folder::query()
                    ->pluck('name');

                $this->assertSame(
                    ['Pasta B'],
                    $folders->all(),
                );
            }
        );
    }

    public function test_cliente_criado_no_contexto_recebe_organization_id_automaticamente(): void
    {
        $organization =
            Organization::factory()->create();

        $this->runAsOrganization(
            $organization,
            function () use (
                $organization
            ): void {
                $client =
                    Client::query()->create([
                        'name' =>
                        'Cliente automático',

                        'document' =>
                        '12345678901',
                    ]);

                $this->assertSame(
                    $organization->id,
                    $client->organization_id,
                );
            }
        );
    }

    public function test_pasta_criada_no_contexto_recebe_organization_id_automaticamente(): void
    {
        $organization =
            Organization::factory()->create();

        $this->runAsOrganization(
            $organization,
            function () use (
                $organization
            ): void {
                $folder =
                    Folder::query()->create([
                        'name' =>
                        'Pasta automática',
                    ]);

                $this->assertSame(
                    $organization->id,
                    $folder->organization_id,
                );
            }
        );
    }

    public function test_nao_permite_criar_cliente_em_organizacao_diferente_do_contexto(): void
    {
        [$organizationA, $organizationB] =
            $this->organizations();

        $this->expectException(
            LogicException::class
        );

        $this->runAsOrganization(
            $organizationA,
            function () use (
                $organizationB
            ): void {
                Client::query()->create([
                    'organization_id' =>
                    $organizationB->id,

                    'name' =>
                    'Cliente inválido',

                    'document' =>
                    '12345678901',
                ]);
            }
        );
    }

    public function test_organization_id_e_imutavel_apos_criacao(): void
    {
        [$organizationA, $organizationB] =
            $this->organizations();

        $client =
            Client::factory()
            ->for($organizationA)
            ->create();

        $this->expectException(
            LogicException::class
        );

        $this->runAsOrganization(
            $organizationA,
            function () use (
                $client,
                $organizationB
            ): void {
                $scopedClient =
                    Client::query()
                    ->findOrFail(
                        $client->id
                    );

                $scopedClient
                    ->organization_id =
                    $organizationB->id;

                $scopedClient->save();
            }
        );
    }

    public function test_scope_pode_ser_removido_explicitamente_para_operacoes_de_plataforma(): void
    {
        [$organizationA, $organizationB] =
            $this->organizations();

        Client::factory()
            ->for($organizationA)
            ->create();

        Client::factory()
            ->for($organizationB)
            ->create();

        $this->runAsOrganization(
            $organizationA,
            function (): void {
                $this->assertSame(
                    1,
                    Client::query()->count(),
                );

                $this->assertSame(
                    2,
                    Client::withoutGlobalScope(
                        OrganizationScope::class
                    )->count(),
                );
            }
        );
    }

    private function organizations(): array
    {
        return [
            Organization::factory()
                ->create(),

            Organization::factory()
                ->create(),
        ];
    }

    private function runAsOrganization(
        Organization $organization,
        callable $callback,
    ): mixed {
        $currentOrganization =
            app(
                CurrentOrganization::class
            );

        $currentOrganization->set(
            $organization
        );

        try {
            return $callback();
        } finally {
            $currentOrganization->clear();
        }
    }
}
