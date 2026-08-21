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

        $attentionDeadlines =
            $folder
            ->deadlines()
            ->where(
                'status',
                'pending',
            )
            ->orderBy(
                'due_at'
            )
            ->orderBy(
                'id'
            )
            ->limit(3)
            ->get([
                'id',
                'folder_id',
                'title',
                'description',
                'due_at',
                'status',
            ])
            ->map(function ($deadline) {
                $dueAt =
                    $deadline->due_at;

                $urgency =
                    match (true) {
                        $dueAt->lt(
                            now()->startOfDay()
                        ) =>
                        'overdue',

                        $dueAt->between(
                            now()->startOfDay(),
                            now()->endOfDay(),
                        ) =>
                        'today',

                        default =>
                        'upcoming',
                    };

                return [
                    'id' =>
                    $deadline->id,

                    'title' =>
                    $deadline->title,

                    'description' =>
                    $deadline->description,

                    'due_at' =>
                    $dueAt,

                    'status' =>
                    $deadline->status,

                    'urgency' =>
                    $urgency,
                ];
            })
            ->values();

        $attentionTasks =
            $folder
            ->tasks()
            ->where(
                'status',
                'pending',
            )
            ->get([
                'id',
                'folder_id',
                'title',
                'description',
                'priority',
                'due_at',
                'status',
            ])
            ->map(function ($task) {
                $dueAt =
                    $task->due_at;

                $urgency =
                    match (true) {
                        $dueAt === null =>
                        'unscheduled',

                        $dueAt->lt(
                            now()->startOfDay()
                        ) =>
                        'overdue',

                        $dueAt->between(
                            now()->startOfDay(),
                            now()->endOfDay(),
                        ) =>
                        'today',

                        default =>
                        'upcoming',
                    };

                return [
                    'id' =>
                    $task->id,

                    'title' =>
                    $task->title,

                    'description' =>
                    $task->description,

                    'priority' =>
                    $task->priority,

                    'due_at' =>
                    $dueAt,

                    'status' =>
                    $task->status,

                    'urgency' =>
                    $urgency,
                ];
            })
            ->sort(function (array $left, array $right) {
                $urgencyOrder = [
                    'overdue' =>
                    0,

                    'today' =>
                    1,

                    'unscheduled' =>
                    2,

                    'upcoming' =>
                    3,
                ];

                $priorityOrder = [
                    'high' =>
                    0,

                    'medium' =>
                    1,

                    'low' =>
                    2,
                ];

                $leftUrgency =
                    $urgencyOrder[$left['urgency']] ?? 4;

                $rightUrgency =
                    $urgencyOrder[$right['urgency']] ?? 4;

                if (
                    $leftUrgency !==
                    $rightUrgency
                ) {
                    return $leftUrgency <=>
                        $rightUrgency;
                }

                $leftPriority =
                    $priorityOrder[$left['priority']] ?? 3;

                $rightPriority =
                    $priorityOrder[$right['priority']] ?? 3;

                if (
                    $leftPriority !==
                    $rightPriority
                ) {
                    return $leftPriority <=>
                        $rightPriority;
                }

                $leftTimestamp =
                    $left['due_at']
                    ? $left['due_at']->timestamp
                    : PHP_INT_MAX;

                $rightTimestamp =
                    $right['due_at']
                    ? $right['due_at']->timestamp
                    : PHP_INT_MAX;

                if (
                    $leftTimestamp !==
                    $rightTimestamp
                ) {
                    return $leftTimestamp <=>
                        $rightTimestamp;
                }

                return $left['id'] <=>
                    $right['id'];
            })
            ->take(3)
            ->values();

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

                'attention' => [
                    'deadlines' =>
                    $attentionDeadlines,

                    'tasks' =>
                    $attentionTasks,
                ],

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
