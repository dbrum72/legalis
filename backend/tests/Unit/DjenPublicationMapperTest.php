<?php

namespace Tests\Unit;

use App\Integrations\Djen\DjenPublicationMapper;
use Tests\TestCase;

class DjenPublicationMapperTest extends TestCase
{
    public function test_normaliza_variantes_conhecidas_do_payload_do_djen(): void
    {
        $mapped =
            (new DjenPublicationMapper)
                ->map([
                    'numeroComunicacao' => '123456',

                    'dataDisponibilizacao' => '2026-08-27T03:00:00-03:00',

                    'numeroProcesso' => '5000000-00.2026.8.21.0001',

                    'sigla_tribunal' => 'TJRS',

                    'tipo_documento' => 'Despacho',

                    'conteudo' => 'Conteúdo da publicação.',
                ]);

        $this->assertSame(
            '123456',
            $mapped['external_id'],
        );

        $this->assertSame(
            '50000000020268210001',
            $mapped['normalized_process_number'],
        );

        $this->assertSame(
            '2026-08-27',
            $mapped['available_on'],
        );

        $this->assertSame(
            'Conteúdo da publicação.',
            $mapped['content'],
        );

        $this->assertSame(
            64,
            strlen(
                $mapped['payload_hash']
            ),
        );
    }

    public function test_gera_identificador_estavel_quando_api_nao_fornece_id_ou_hash(): void
    {
        $mapper =
            new DjenPublicationMapper;

        $payload = [
            'texto' => 'Publicação sem identificador explícito.',
        ];

        $first =
            $mapper->map(
                $payload
            );

        $second =
            $mapper->map(
                $payload
            );

        $this->assertSame(
            $first['external_id'],
            $second['external_id'],
        );

        $this->assertSame(
            $first['payload_hash'],
            $first['external_id'],
        );
    }

    public function test_nao_normaliza_numero_que_nao_seja_padrao_cnj(): void
    {
        $mapper =
            new DjenPublicationMapper;

        $this->assertNull(
            $mapper->normalizeProcessNumber(
                '123456'
            )
        );
    }
}
