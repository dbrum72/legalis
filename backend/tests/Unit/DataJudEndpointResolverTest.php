<?php

namespace Tests\Unit;

use App\Integrations\DataJud\DataJudEndpointResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataJudEndpointResolverTest extends TestCase
{
    #[DataProvider('processes')]
    public function test_resolve_o_indice_pelo_numero_cnj(
        string $number,
        string $alias,
    ): void {
        $this->assertSame(
            $alias,
            (new DataJudEndpointResolver)->resolve($number),
        );
    }

    public static function processes(): array
    {
        return [
            'TJRS' => ['5000000-00.2026.8.21.0001', 'tjrs'],
            'TJSP' => ['5000000-00.2026.8.26.0001', 'tjsp'],
            'TRF4' => ['5000000-00.2026.4.04.0001', 'trf4'],
            'TRT4' => ['5000000-00.2026.5.04.0001', 'trt4'],
            'TRE-RS' => ['5000000-00.2026.6.21.0001', 'tre-rs'],
        ];
    }

    public function test_rejeita_numero_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DataJudEndpointResolver)->resolve('123');
    }
}
