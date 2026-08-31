<?php

namespace App\Integrations\DataJud\Contracts;

interface DataJudClient
{
    public function findProcess(
        string $alias,
        string $processNumber,
    ): ?array;
}
