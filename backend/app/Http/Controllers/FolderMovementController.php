<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderMovementStoreRequest;
use App\Models\Folder;
use App\Models\FolderMovement;
use Illuminate\Http\JsonResponse;

class FolderMovementController extends Controller
{
    public function index(
        Folder $folder,
    ): JsonResponse {
        $movements =
            $folder
            ->movements()
            ->with([
                'user:id,name',
            ])
            ->orderByDesc(
                'occurred_at'
            )
            ->orderByDesc(
                'id'
            )
            ->get([
                'id',
                'folder_id',
                'user_id',
                'occurred_at',
                'title',
                'description',
                'created_at',
                'updated_at',
            ]);

        return response()->json(
            $movements
        );
    }

    public function store(
        FolderMovementStoreRequest $request,
        Folder $folder,
    ): JsonResponse {
        $user =
            $request->user(
                'api'
            );

        $movement =
            $folder
            ->movements()
            ->create([
                'user_id' =>
                $user?->id,

                'occurred_at' =>
                $request
                    ->validated(
                        'occurred_at'
                    ),

                'title' =>
                $request
                    ->validated(
                        'title'
                    ),

                'description' =>
                $request
                    ->validated(
                        'description'
                    ),
            ]);

        $movement->load([
            'user:id,name',
        ]);

        return response()->json(
            $movement,
            201,
        );
    }

    public function destroy(
        Folder $folder,
        FolderMovement $movement,
    ): JsonResponse {
        $this
            ->ensureMovementBelongsToFolder(
                $folder,
                $movement,
            );

        $movement->delete();

        return response()->json(
            null,
            204,
        );
    }

    private function ensureMovementBelongsToFolder(
        Folder $folder,
        FolderMovement $movement,
    ): void {
        if (
            (int) $movement->folder_id !==
            (int) $folder->id
        ) {
            abort(
                404
            );
        }
    }
}
