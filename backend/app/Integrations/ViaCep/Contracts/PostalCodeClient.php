<?php

namespace App\Integrations\ViaCep\Contracts;

interface PostalCodeClient
{
    public function find(string $postalCode): ?array;
}
