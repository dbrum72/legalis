<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderDeadlineStoreRequest;
use App\Models\Folder;
use App\Models\FolderDeadline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderDeadlineController extends Controller
{
    public function index(
        Folder $folder,
    ): JsonResponse {
        $deadlines =
            $folder
            ->deadlines()
            ->with([
                'user:id,name',
            ])
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
                'due_at',
                'status',
                'completed_at',
                'created_at',
                'updated_at',
            ]);

        return response()->json(
            $deadlines
        );
    }

    public function store(
        FolderDeadlineStoreRequest $request,
        Folder $folder,
    ): JsonResponse {
        $user =
            $request->user(
                'api'
            );

        $deadline =
            $folder
            ->deadlines()
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

                'due_at' =>
                $request->validated(
                    'due_at'
                ),

                'status' =>
                'pending',

                'completed_at' =>
                null,
            ]);

        $deadline->load([
            'user:id,name',
        ]);

        return response()->json(
            $deadline,
            201,
        );
    }

    public function complete(
        Request $request,
        Folder $folder,
        FolderDeadline $deadline,
    ): JsonResponse {
        $this->ensureDeadlineBelongsToFolder(
            $folder,
            $deadline,
        );

        $deadline->update([
            'status' =>
            'completed',

            'completed_at' =>
            now(),
        ]);

        $deadline
            ->refresh()
            ->load([
                'user:id,name',
            ]);

        return response()->json(
            $deadline
        );
    }

    public function destroy(
        Folder $folder,
        FolderDeadline $deadline,
    ): JsonResponse {
        $this->ensureDeadlineBelongsToFolder(
            $folder,
            $deadline,
        );

        $deadline->delete();

        return response()->json(
            null,
            204,
        );
    }

    private function ensureDeadlineBelongsToFolder(
        Folder $folder,
        FolderDeadline $deadline,
    ): void {
        if (
            (int) $deadline->folder_id !==
            (int) $folder->id
        ) {
            abort(
                404
            );
        }
    }
}
