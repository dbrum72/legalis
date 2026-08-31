<?php

namespace App\Integrations\DataJud;

use App\Integrations\DataJud\Contracts\DataJudClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpDataJudClient implements DataJudClient
{
    public function findProcess(
        string $alias,
        string $processNumber,
    ): ?array {
        $response = $this
            ->request()
            ->post(
                sprintf(
                    '%s/api_publica_%s/_search',
                    rtrim((string) config('services.datajud.base_url'), '/'),
                    $alias,
                ),
                [
                    'size' => 1,
                    'query' => [
                        'term' => [
                            'numeroProcesso' => $processNumber,
                        ],
                    ],
                ],
            );

        $response->throw();

        $source = $response->json('hits.hits.0._source');

        return is_array($source) ? $source : null;
    }

    private function request(): PendingRequest
    {
        $apiKey = trim((string) config('services.datajud.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException(
                'DATAJUD_API_KEY não está configurada.',
            );
        }

        return Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'Authorization' => 'APIKey '.$apiKey,
                'User-Agent' => (string) config('services.datajud.user_agent'),
            ])
            ->timeout((int) config('services.datajud.timeout'))
            ->retry(2, 250);
    }
}
