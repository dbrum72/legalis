<?php

namespace App\Integrations\Djen;

use Carbon\CarbonImmutable;
use JsonException;
use Throwable;

class DjenPublicationMapper
{
    /**
     * @throws JsonException
     */
    public function map(
        array $payload,
    ): array {
        $encodedPayload =
            json_encode(
                $payload,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE,
            );

        $payloadHash =
            hash(
                'sha256',
                $encodedPayload,
            );

        $processNumber =
            $this->stringValue(
                $payload,
                [
                    'numero_processo',
                    'numeroProcesso',
                    'processo',
                ],
            );

        $sourceHash =
            $this->stringValue(
                $payload,
                [
                    'hash',
                    'hashComunicacao',
                    'hash_comunicacao',
                ],
            );

        $externalId =
            $this->stringValue(
                $payload,
                [
                    'id',
                    'numeroComunicacao',
                    'numero_comunicacao',
                ],
            )
            ?? $sourceHash
            ?? $payloadHash;

        return [
            'source' => 'djen',

            'external_id' => $externalId,

            'source_hash' => $sourceHash,

            'process_number' => $processNumber,

            'normalized_process_number' => $this->normalizeProcessNumber(
                $processNumber
            ),

            'court_acronym' => $this->stringValue(
                $payload,
                [
                    'siglaTribunal',
                    'sigla_tribunal',
                    'tribunal',
                ],
            ),

            'judicial_body' => $this->stringValue(
                $payload,
                [
                    'nomeOrgao',
                    'nome_orgao',
                    'orgao',
                ],
            ),

            'communication_type' => $this->stringValue(
                $payload,
                [
                    'tipoComunicacao',
                    'tipo_comunicacao',
                ],
            ),

            'document_type' => $this->stringValue(
                $payload,
                [
                    'tipoDocumento',
                    'tipo_documento',
                ],
            ),

            'medium' => $this->stringValue(
                $payload,
                [
                    'meio',
                ],
            ),

            'available_on' => $this->dateValue(
                $payload,
                [
                    'data_disponibilizacao',
                    'dataDisponibilizacao',
                ],
            ),

            'published_on' => $this->dateValue(
                $payload,
                [
                    'data_publicacao',
                    'dataPublicacao',
                ],
            ),

            'content' => $this->stringValue(
                $payload,
                [
                    'texto',
                    'conteudo',
                    'text',
                ],
            )
                ?? '',

            'recipients' => $this->arrayValue(
                $payload,
                [
                    'destinatarios',
                    'destinatario',
                ],
            ),

            'lawyers' => $this->arrayValue(
                $payload,
                [
                    'destinatarioadvogados',
                    'destinatario_advogados',
                    'advogados',
                ],
            ),

            'raw_payload' => $payload,

            'payload_hash' => $payloadHash,
        ];
    }

    public function normalizeProcessNumber(
        ?string $processNumber,
    ): ?string {
        if ($processNumber === null) {
            return null;
        }

        $normalized =
            preg_replace(
                '/\D+/',
                '',
                $processNumber,
            );

        return strlen($normalized) !== 20
            ? null
            : $normalized;
    }

    private function stringValue(
        array $payload,
        array $keys,
    ): ?string {
        foreach ($keys as $key) {
            $value =
                data_get(
                    $payload,
                    $key,
                );

            if (! is_scalar($value)) {
                continue;
            }

            $value =
                trim(
                    (string) $value
                );

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function arrayValue(
        array $payload,
        array $keys,
    ): ?array {
        foreach ($keys as $key) {
            $value =
                data_get(
                    $payload,
                    $key,
                );

            if (is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    private function dateValue(
        array $payload,
        array $keys,
    ): ?string {
        $value =
            $this->stringValue(
                $payload,
                $keys,
            );

        if ($value === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse(
                $value,
                (string) config(
                    'services.djen.timezone',
                    'America/Sao_Paulo',
                ),
            )->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
