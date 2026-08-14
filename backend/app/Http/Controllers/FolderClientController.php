<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderClientStoreRequest;
use App\Http\Requests\FolderClientUpdateRequest;
use App\Models\Folder;
use App\Models\FolderClient;
use Illuminate\Http\JsonResponse;

class FolderClientController extends Controller
{
    public function store(
        FolderClientStoreRequest $request,
        Folder $folder
    ): JsonResponse {
        $folderClient = $folder
            ->folderClients()
            ->create(
                $request->validated()
            );

        $folderClient->load([
            'client',
            'qualification',
        ]);

        return response()->json(
            $folderClient,
            201
        );
    }

    public function update(
        FolderClientUpdateRequest $request,
        Folder $folder,
        FolderClient $folderClient
    ): JsonResponse {
        $folderClient->update(
            $request->validated()
        );

        return response()->json(
            $folderClient
                ->fresh()
                ->load([
                    'client',
                    'qualification',
                ])
        );
    }

    public function destroy(
        Folder $folder,
        FolderClient $folderClient
    ): JsonResponse {
        $folderClient->delete();

        return response()->json(
            null,
            204
        );
    }
}