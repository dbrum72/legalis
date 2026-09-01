<?php

namespace App\Integrations\ViaCep;

use App\Integrations\ViaCep\Contracts\PostalCodeClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class HttpViaCepClient implements PostalCodeClient
{
    public function find(string $postalCode): ?array
    {
        $response = $this
            ->request()
            ->get(
                sprintf(
                    '%s/ws/%s/json/',
                    rtrim((string) config('services.viacep.base_url'), '/'),
                    $postalCode,
                ),
            );

        $response->throw();

        if ($response->json('erro') === true) {
            return null;
        }

        return [
            'postal_code' => $response->json('cep'),
            'address' => $response->json('logradouro'),
            'district' => $response->json('bairro'),
            'city' => $response->json('localidade'),
            'state' => $response->json('uf'),
        ];
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withUserAgent((string) config('services.viacep.user_agent'))
            ->timeout((int) config('services.viacep.timeout'))
            ->retry(2, 200);
    }
}
