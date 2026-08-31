<?php

namespace App\Integrations\Djen\Data;

final readonly class DjenSearchPage
{
    public function __construct(
        public array $items,
        public bool $hasMore,
    ) {}
}
