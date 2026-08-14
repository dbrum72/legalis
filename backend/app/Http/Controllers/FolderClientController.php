<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderClientStoreRequest;
use App\Http\Requests\FolderClientUpdateRequest;
use App\Models\Folder;
use App\Models\FolderClient;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;

class FolderClientController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $currentOrganization,
    ) {
    }

    public function store(
        FolderClientStoreRequest $request,
        string $folder,
    ): JsonResponse {
        $folder = $this->findFolder(
            $folder
        );

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
        string $folder,
        string $folderClient,
    ): JsonResponse {
        $folder = $this->findFolder(
            $folder
        );

        $folderClient =
            $this->findFolderClient(
                $folder,
                $folderClient,
            );

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
        string $folder,
        string $folderClient,
    ): JsonResponse {
        $folder = $this->findFolder(
            $folder
        );

        $folderClient =
            $this->findFolderClient(
                $folder,
                $folderClient,
            );

        $folderClient->delete();

        return response()->json(
            null,
            204
        );
    }

    private function findFolder(
        string|int $folderId,
    ): Folder {
        return $this
            ->currentOrganization
            ->get()
            ->folders()
            ->whereKey($folderId)
            ->firstOrFail();
    }

    private function findFolderClient(
        Folder $folder,
        string|int $folderClientId,
    ): FolderClient {
        return $folder
            ->folderClients()
            ->whereKey($folderClientId)
            ->firstOrFail();
    }
}