<?php

namespace Tests\Unit;

use App\Integrations\Djen\HttpDjenClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpDjenClientTest extends TestCase
{
    public function test_count_controla_paginacao_da_resposta_oficial(): void
    {
        config([
            'services.djen.base_url' => 'https://comunicaapi.pje.jus.br/api/v1',

            'services.djen.per_page' => 2,
        ]);

        Http::fakeSequence()
            ->push([
                'count' => 3,

                'items' => [
                    [
                        'id' => 1,
                    ],

                    [
                        'id' => 2,
                    ],
                ],
            ])
            ->push([
                'count' => 3,

                'items' => [
                    [
                        'id' => 3,
                    ],
                ],
            ]);

        $client =
            new HttpDjenClient;

        $firstPage =
            $client->searchByBarRegistration(
                '93556',
                'RS',
                CarbonImmutable::parse(
                    '2026-08-25'
                ),
                CarbonImmutable::parse(
                    '2026-08-28'
                ),
                1,
            );

        $secondPage =
            $client->searchByBarRegistration(
                '93556',
                'RS',
                CarbonImmutable::parse(
                    '2026-08-25'
                ),
                CarbonImmutable::parse(
                    '2026-08-28'
                ),
                2,
            );

        $this->assertCount(
            2,
            $firstPage->items,
        );

        $this->assertTrue(
            $firstPage->hasMore
        );

        $this->assertCount(
            1,
            $secondPage->items,
        );

        $this->assertFalse(
            $secondPage->hasMore
        );
    }
}
