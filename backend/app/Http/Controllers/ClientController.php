<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Client::query()
            ->with('maritalStatus')
            ->orderBy('name')
            ->get();

        return response()->json($clients);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = Client::create(
            $request->validated()
        );

        $client->load('maritalStatus');

        return response()->json(
            $client,
            201
        );
    }

    public function show(Client $client): JsonResponse
    {
        $client->load('maritalStatus');

        return response()->json($client);
    }

    public function update(
        ClientRequest $request,
        Client $client,
    ): JsonResponse {
        $client->update(
            $request->validated()
        );

        $client->load('maritalStatus');

        return response()->json($client);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(
            null,
            204
        );
    }
}
