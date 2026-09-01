<?php

namespace App\Http\Controllers;

use App\Integrations\ViaCep\Contracts\PostalCodeClient;
use Illuminate\Http\JsonResponse;

class PostalCodeController extends Controller
{
    public function show(
        string $postalCode,
        PostalCodeClient $client,
    ): JsonResponse {
        $address = $client->find($postalCode);

        abort_if(
            $address === null,
            404,
            'CEP não encontrado.',
        );

        return response()->json([
            'data' => $address,
        ]);
    }
}
