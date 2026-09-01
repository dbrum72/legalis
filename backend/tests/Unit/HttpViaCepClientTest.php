<?php

namespace Tests\Unit;

use App\Integrations\ViaCep\HttpViaCepClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpViaCepClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.viacep.base_url' => 'https://viacep.test',
            'services.viacep.timeout' => 5,
            'services.viacep.user_agent' => 'Legalis/Test',
        ]);
    }

    public function test_mapeia_endereco_sem_expor_resposta_do_viacep(): void
    {
        Http::fake([
            'https://viacep.test/ws/01001000/json/' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'complemento' => 'lado ímpar',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
                'ibge' => '3550308',
            ]),
        ]);

        $address = (new HttpViaCepClient)->find('01001000');

        $this->assertSame([
            'postal_code' => '01001-000',
            'address' => 'Praça da Sé',
            'district' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
        ], $address);

        Http::assertSent(fn ($request) => $request->url() === 'https://viacep.test/ws/01001000/json/'
            && $request->hasHeader('User-Agent', 'Legalis/Test')
        );
    }

    public function test_retorna_nulo_quando_cep_nao_existe(): void
    {
        Http::fake([
            'https://viacep.test/ws/99999999/json/' => Http::response([
                'erro' => true,
            ]),
        ]);

        $this->assertNull(
            (new HttpViaCepClient)->find('99999999'),
        );
    }
}
