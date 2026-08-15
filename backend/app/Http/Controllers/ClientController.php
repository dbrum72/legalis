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

        return response()->json(
            $clients
        );
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

        $client->load(
            'maritalStatus'
        );

        return response()->json(
            $client,
            201,
        );
    }

    public function show(
        Client $client,
    ): JsonResponse {
        $client->load(
            'maritalStatus'
        );

        return response()->json(
            $client
        );
    }

    public function update(
        ClientRequest $request,
        Client $client,
    ): JsonResponse {
        $client->update(
            $request->validated()
        );

        $client->load(
            'maritalStatus'
        );

        return response()->json(
            $client
        );
    }

    public function destroy(
        Client $client,
    ): JsonResponse {
        $client->delete();

        return response()->json(
            null,
            204,
        );
    }
}
