<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderTaskStoreRequest;
use App\Models\Folder;
use App\Models\FolderTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderTaskController extends Controller
{
    public function index(
        Folder $folder,
    ): JsonResponse {
        $tasks =
            $folder
            ->tasks()
            ->with([
                'user:id,name',
            ])
            ->orderByRaw(
                "CASE WHEN status = 'pending' THEN 0 ELSE 1 END"
            )
            ->orderByRaw(
                'CASE WHEN due_at IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy(
                'due_at'
            )
            ->orderBy(
                'id'
            )
            ->get([
                'id',
                'folder_id',
                'user_id',
                'title',
                'description',
                'priority',
                'due_at',
                'status',
                'completed_at',
                'created_at',
                'updated_at',
            ]);

        return response()->json(
            $tasks
        );
    }

    public function store(
        FolderTaskStoreRequest $request,
        Folder $folder,
    ): JsonResponse {
        $user =
            $request->user(
                'api'
            );

        $task =
            $folder
            ->tasks()
            ->create([
                'user_id' =>
                $user?->id,

                'title' =>
                $request->validated(
                    'title'
                ),

                'description' =>
                $request->validated(
                    'description'
                ),

                'priority' =>
                $request->validated(
                    'priority'
                ) ??
                    'medium',

                'due_at' =>
                $request->validated(
                    'due_at'
                ),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $task->load([
            'user:id,name',
        ]);

        return response()->json(
            $task,
            201,
        );
    }

    public function complete(
        Request $request,
        Folder $folder,
        FolderTask $task,
    ): JsonResponse {
        $this->ensureTaskBelongsToFolder(
            $folder,
            $task,
        );

        $task->update([
            'status' =>
            'completed',

            'completed_at' =>
            now(),
        ]);

        $task
            ->refresh()
            ->load([
                'user:id,name',
            ]);

        return response()->json(
            $task
        );
    }

    public function destroy(
        Folder $folder,
        FolderTask $task,
    ): JsonResponse {
        $this->ensureTaskBelongsToFolder(
            $folder,
            $task,
        );

        $task->delete();

        return response()->json(
            null,
            204,
        );
    }

    private function ensureTaskBelongsToFolder(
        Folder $folder,
        FolderTask $task,
    ): void {
        if (
            (int) $task->folder_id !==
            (int) $folder->id
        ) {
            abort(
                404
            );
        }
    }
}
