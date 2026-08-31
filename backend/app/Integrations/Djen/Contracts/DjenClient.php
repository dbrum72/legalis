<?php

namespace App\Integrations\Djen\Contracts;

use App\Integrations\Djen\Data\DjenSearchPage;
use Carbon\CarbonImmutable;

interface DjenClient
{
    public function searchByBarRegistration(
        string $barNumber,
        string $state,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        int $page,
    ): DjenSearchPage;
}
