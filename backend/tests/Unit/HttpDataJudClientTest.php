<?php

namespace Tests\Unit;

use App\Integrations\DataJud\HttpDataJudClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpDataJudClientTest extends TestCase
{
    public function test_consulta_processo_com_chave_publica(): void
    {
        config([
            'services.datajud.base_url' => 'https://datajud.test',
            'services.datajud.api_key' => 'public-key',
            'services.datajud.timeout' => 5,
            'services.datajud.user_agent' => 'Legalis/Test',
        ]);

        Http::fake([
            'https://datajud.test/api_publica_tjrs/_search' => Http::response([
                'hits' => [
                    'hits' => [[
                        '_source' => ['numeroProcesso' => '50000000020268210001'],
                    ]],
                ],
            ]),
        ]);

        $result = (new HttpDataJudClient)->findProcess(
            'tjrs',
            '50000000020268210001',
        );

        $this->assertSame('50000000020268210001', $result['numeroProcesso']);

        Http::assertSent(fn ($request) =>
            $request->hasHeader('Authorization', 'APIKey public-key')
            && $request['query']['term']['numeroProcesso'] === '50000000020268210001'
        );
    }
}
