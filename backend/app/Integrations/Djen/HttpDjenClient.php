<?php

namespace App\Integrations\Djen;

use App\Integrations\Djen\Contracts\DjenClient;
use App\Integrations\Djen\Data\DjenSearchPage;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class HttpDjenClient implements DjenClient
{
    public function searchByBarRegistration(
        string $barNumber,
        string $state,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        int $page,
    ): DjenSearchPage {
        $perPage =
            (int) config(
                'services.djen.per_page',
                50,
            );

        $response =
            $this
                ->request()
                ->get(
                    '/comunicacao',
                    [
                        'numeroOab' => $barNumber,

                        'ufOab' => strtoupper($state),

                        'dataDisponibilizacaoInicio' => $periodStart->toDateString(),

                        'dataDisponibilizacaoFim' => $periodEnd->toDateString(),

                        'pagina' => $page,

                        'itensPorPagina' => $perPage,

                        'meio' => 'D',
                    ],
                );

        $response->throw();

        $payload =
            $response->json();

        if (! is_array($payload)) {
            throw new UnexpectedValueException(
                'A API do DJEN retornou uma resposta inválida.'
            );
        }

        $items =
            $this->extractItems(
                $payload
            );

        return new DjenSearchPage(
            items: $items,
            hasMore: $this->hasMore(
                $payload,
                $items,
                $page,
                $perPage,
            ),
        );
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(
            rtrim(
                (string) config(
                    'services.djen.base_url'
                ),
                '/',
            )
        )
            ->acceptJson()
            ->withUserAgent(
                (string) config(
                    'services.djen.user_agent',
                    'Legalis/1.0',
                )
            )
            ->timeout(
                (int) config(
                    'services.djen.timeout',
                    20,
                )
            )
            ->retry(
                [
                    250,
                    750,
                    1500,
                ],
                throw: false,
            );
    }

    private function extractItems(
        array $payload,
    ): array {
        $candidates = [
            data_get($payload, 'items'),
            data_get($payload, 'data.items'),
            data_get($payload, 'content'),
            data_get($payload, 'data.content'),
            data_get($payload, 'data'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            if (
                $candidate === []
                || array_is_list($candidate)
            ) {
                return array_values(
                    array_filter(
                        $candidate,
                        is_array(...),
                    )
                );
            }
        }

        return [];
    }

    private function hasMore(
        array $payload,
        array $items,
        int $page,
        int $perPage,
    ): bool {
        $explicit =
            data_get(
                $payload,
                'has_next'
            )
            ?? data_get(
                $payload,
                'hasNext'
            )
            ?? data_get(
                $payload,
                'data.has_next'
            )
            ?? data_get(
                $payload,
                'data.hasNext'
            );

        if (is_bool($explicit)) {
            return $explicit;
        }

        $total =
            data_get($payload, 'count')
            ?? data_get($payload, 'data.count')
            ?? data_get($payload, 'total')
            ?? data_get($payload, 'totalElements')
            ?? data_get($payload, 'data.total')
            ?? data_get($payload, 'data.totalElements');

        if (is_numeric($total)) {
            return $page * $perPage < (int) $total;
        }

        return count($items) >= $perPage;
    }
}
