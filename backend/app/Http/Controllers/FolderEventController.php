<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderEventStoreRequest;
use App\Models\Folder;
use App\Models\FolderEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderEventController extends Controller
{
    public function index(
        Folder $folder,
    ): JsonResponse {
        $events =
            $folder
            ->events()
            ->with([
                'user:id,name',
            ])
            ->orderBy(
                'starts_at'
            )
            ->orderBy(
                'id'
            )
            ->get([
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
                'created_at',
                'updated_at',
            ]);

        return response()->json(
            $events
        );
    }

    public function store(
        FolderEventStoreRequest $request,
        Folder $folder,
    ): JsonResponse {
        $user =
            $request->user(
                'api'
            );

        $event =
            $folder
            ->events()
            ->create([
                'user_id' =>
                $user?->id,

                'type' =>
                $request->validated(
                    'type'
                ),

                'title' =>
                $request->validated(
                    'title'
                ),

                'description' =>
                $request->validated(
                    'description'
                ),

                'starts_at' =>
                $request->validated(
                    'starts_at'
                ),

                'ends_at' =>
                $request->validated(
                    'ends_at'
                ),

                'location' =>
                $request->validated(
                    'location'
                ),

                'status' =>
                'scheduled',

                'completed_at' =>
                null,
            ]);

        $event->load([
            'user:id,name',
        ]);

        return response()->json(
            $event,
            201,
        );
    }

    public function complete(
        Request $request,
        Folder $folder,
        FolderEvent $event,
    ): JsonResponse {
        $this->ensureEventBelongsToFolder(
            $folder,
            $event,
        );

        $event->update([
            'status' =>
            'completed',

            'completed_at' =>
            now(),
        ]);

        $event
            ->refresh()
            ->load([
                'user:id,name',
            ]);

        return response()->json(
            $event
        );
    }

    public function destroy(
        Folder $folder,
        FolderEvent $event,
    ): JsonResponse {
        $this->ensureEventBelongsToFolder(
            $folder,
            $event,
        );

        $event->delete();

        return response()->json(
            null,
            204,
        );
    }

    private function ensureEventBelongsToFolder(
        Folder $folder,
        FolderEvent $event,
    ): void {
        if (
            (int) $event->folder_id !==
            (int) $folder->id
        ) {
            abort(
                404
            );
        }
    }
}
