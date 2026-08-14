<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderStoreRequest;
use App\Http\Requests\FolderUpdateRequest;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;

class FolderController extends Controller
{
    public function index(): JsonResponse
    {
        $folders = Folder::query()
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }

    public function store(FolderStoreRequest $request): JsonResponse
    {
        $folder = Folder::query()->create(
            $request->validated()
        );

        return response()->json(
            $folder,
            201
        );
    }

    public function show(Folder $folder): JsonResponse {
        $folder->load([
            'folderClients.client',
            'folderClients.qualification',
        ]);

        return response()->json(
            $folder
        );
    }

    public function update(FolderUpdateRequest $request, Folder $folder): JsonResponse
    {
        $folder->update(
            $request->validated()
        );

        return response()->json(
            $folder->fresh()
        );
    }

    public function destroy(Folder $folder): JsonResponse
    {
        $folder->delete();

        return response()->json(
            null,
            204
        );
    }
}
