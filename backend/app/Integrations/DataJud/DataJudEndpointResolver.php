<?php

namespace App\Integrations\DataJud;

use InvalidArgumentException;

class DataJudEndpointResolver
{
    private const STATE_CODES = [
        '01' => 'ac', '02' => 'al', '03' => 'ap', '04' => 'am',
        '05' => 'ba', '06' => 'ce', '07' => 'dft', '08' => 'es',
        '09' => 'go', '10' => 'ma', '11' => 'mt', '12' => 'ms',
        '13' => 'mg', '14' => 'pa', '15' => 'pb', '16' => 'pr',
        '17' => 'pe', '18' => 'pi', '19' => 'rj', '20' => 'rn',
        '21' => 'rs', '22' => 'ro', '23' => 'rr', '24' => 'sc',
        '25' => 'se', '26' => 'sp', '27' => 'to',
    ];

    public function normalizeProcessNumber(string $processNumber): string
    {
        $normalized = preg_replace('/\D+/', '', $processNumber) ?? '';

        if (strlen($normalized) !== 20) {
            throw new InvalidArgumentException(
                'A pasta precisa ter um número processual CNJ válido.',
            );
        }

        return $normalized;
    }

    public function resolve(string $processNumber): string
    {
        $number = $this->normalizeProcessNumber($processNumber);
        $justice = $number[13];
        $tribunal = substr($number, 14, 2);

        return match ($justice) {
            '3' => 'stj',
            '4' => 'trf'.(int) $tribunal,
            '5' => $tribunal === '00' ? 'tst' : 'trt'.(int) $tribunal,
            '6' => $tribunal === '00' ? 'tse' : 'tre-'.$this->state($tribunal),
            '7' => 'stm',
            '8' => 'tj'.$this->state($tribunal),
            '9' => 'tjm'.$this->militaryState($tribunal),
            default => throw new InvalidArgumentException(
                'O ramo de Justiça deste processo não é atendido pela API Pública do DataJud.',
            ),
        };
    }

    private function state(string $code): string
    {
        return self::STATE_CODES[$code]
            ?? throw new InvalidArgumentException(
                'Não foi possível identificar o tribunal pelo número CNJ.',
            );
    }

    private function militaryState(string $code): string
    {
        $state = $this->state($code);

        if (! in_array($state, ['mg', 'rs', 'sp'], true)) {
            throw new InvalidArgumentException(
                'O tribunal militar estadual não é atendido pela API Pública do DataJud.',
            );
        }

        return $state;
    }
}
