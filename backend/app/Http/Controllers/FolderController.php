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

        $nextEvent =
            $folder
            ->events()
            ->where(
                'status',
                'scheduled',
            )
            ->where(
                'starts_at',
                '>=',
                now(),
            )
            ->orderBy(
                'starts_at'
            )
            ->orderBy(
                'id'
            )
            ->first([
                'id',
                'folder_id',
                'user_id',
                'type',
                'title',
                'description',
                'starts_at',
                'ends_at',
                'location',
                'status',
                'completed_at',
            ]);

        $latestMovement =
            $folder
            ->movements()
            ->orderByDesc(
                'occurred_at'
            )
            ->orderByDesc(
                'id'
            )
            ->first([
                'id',
                'folder_id',
                'user_id',
                'occurred_at',
                'title',
                'description',
            ]);

        $folder->setAttribute(
            'summary',
            [
                'documents_count' =>
                $folder
                    ->documents()
                    ->count(),

                'pending_tasks_count' =>
                $folder
                    ->tasks()
                    ->where(
                        'status',
                        'pending',
                    )
                    ->count(),

                'pending_deadlines_count' =>
                $folder
                    ->deadlines()
                    ->where(
                        'status',
                        'pending',
                    )
                    ->count(),

                'next_event' =>
                $nextEvent,

                'latest_movement' =>
                $latestMovement,
            ],
        );

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
