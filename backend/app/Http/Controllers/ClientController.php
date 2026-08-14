<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $currentOrganization,
    ) {}

    public function index(): JsonResponse
    {
        $clients = $this
            ->currentOrganization
            ->get()
            ->clients()
            ->with('maritalStatus')
            ->orderBy('name')
            ->get();

        return response()->json($clients);
    }

    public function store(
        ClientRequest $request,
    ): JsonResponse {
        $client = $this
            ->currentOrganization
            ->get()
            ->clients()
            ->create(
                $request->validated()
            );

        $client->load('maritalStatus');

        return response()->json(
            $client,
            201
        );
    }

    public function show(
        string $client,
    ): JsonResponse {
        $client = $this->findClient(
            $client
        );

        $client->load('maritalStatus');

        return response()->json(
            $client
        );
    }

    public function update(
        ClientRequest $request,
        string $client,
    ): JsonResponse {
        $client = $this->findClient(
            $client
        );

        $client->update(
            $request->validated()
        );

        $client->load('maritalStatus');

        return response()->json(
            $client
        );
    }

    public function destroy(
        string $client,
    ): JsonResponse {
        $client = $this->findClient(
            $client
        );

        $client->delete();

        return response()->json(
            null,
            204
        );
    }

    private function findClient(
        string|int $clientId,
    ): Client {
        return $this
            ->currentOrganization
            ->get()
            ->clients()
            ->whereKey($clientId)
            ->firstOrFail();
    }
}
