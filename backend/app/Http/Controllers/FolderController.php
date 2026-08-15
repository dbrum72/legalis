<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderStoreRequest;
use App\Http\Requests\FolderUpdateRequest;
use App\Models\Folder;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;

class FolderController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $currentOrganization,
    ) {}

    public function index(): JsonResponse
    {
        $folders = $this
            ->currentOrganization
            ->get()
            ->folders()
            ->orderBy('name')
            ->get();

        return response()->json(
            $folders
        );
    }

    public function store(
        FolderStoreRequest $request,
    ): JsonResponse {
        $folder = $this
            ->currentOrganization
            ->get()
            ->folders()
            ->create(
                $request->validated()
            );

        return response()->json(
            $folder,
            201,
        );
    }

    public function show(
        Folder $folder,
    ): JsonResponse {
        $folder->load([
            'folderClients.client',
            'folderClients.qualification',
        ]);

        return response()->json(
            $folder
        );
    }

    public function update(
        FolderUpdateRequest $request,
        Folder $folder,
    ): JsonResponse {
        $folder->update(
            $request->validated()
        );

        return response()->json(
            $folder->fresh()
        );
    }

    public function destroy(
        Folder $folder,
    ): JsonResponse {
        $folder->delete();

        return response()->json(
            null,
            204,
        );
    }
}
