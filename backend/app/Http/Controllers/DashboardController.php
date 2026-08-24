<?php

namespace App\Http\Controllers;

use App\Models\FolderDeadline;
use App\Models\FolderEvent;
use App\Models\FolderTask;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $organization =
            $currentOrganization->get();

        $user =
            auth('api')->user();

        /*
        |--------------------------------------------------------------------------
        | Pastas recentes
        |--------------------------------------------------------------------------
        */

        $recentFolders =
            $organization
            ->folders()
            ->orderByDesc(
                'created_at'
            )
            ->limit(5)
            ->get([
                'id',
                'name',
                'process_number',
                'created_at',
            ]);

        /*
        |--------------------------------------------------------------------------
        | IDs das pastas da organização atual
        |--------------------------------------------------------------------------
        */

        $organizationFolderIds =
            $organization
            ->folders()
            ->select(
                'id'
            );

        /*
        |--------------------------------------------------------------------------
        | Resumo operacional
        |--------------------------------------------------------------------------
        */

        $pendingTasks =
            FolderTask::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'pending',
            )
            ->count();

        $pendingDeadlines =
            FolderDeadline::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'pending',
            )
            ->count();

        $upcomingEvents =
            FolderEvent::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'scheduled',
            )
            ->where(
                'starts_at',
                '>=',
                now(),
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Contadores da Central de Atenção
        |--------------------------------------------------------------------------
        */

        $overdueTasks =
            FolderTask::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'pending',
            )
            ->whereNotNull(
                'due_at'
            )
            ->where(
                'due_at',
                '<',
                now(),
            )
            ->count();

        $overdueDeadlines =
            FolderDeadline::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'pending',
            )
            ->whereNotNull(
                'due_at'
            )
            ->where(
                'due_at',
                '<',
                now(),
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Central de Atenção - Tarefas vencidas
        |--------------------------------------------------------------------------
        */

        $attentionOverdueTasks =
            FolderTask::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'pending',
            )
            ->whereNotNull(
                'due_at'
            )
            ->where(
                'due_at',
                '<',
                now(),
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->orderBy(
                'due_at'
            )
            ->orderBy(
                'id'
            )
            ->limit(5)
            ->get([
                'id',
                'folder_id',
                'title',
                'priority',
                'due_at',
                'status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Central de Atenção - Prazos vencidos
        |--------------------------------------------------------------------------
        */

        $attentionOverdueDeadlines =
            FolderDeadline::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'pending',
            )
            ->whereNotNull(
                'due_at'
            )
            ->where(
                'due_at',
                '<',
                now(),
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->orderBy(
                'due_at'
            )
            ->orderBy(
                'id'
            )
            ->limit(5)
            ->get([
                'id',
                'folder_id',
                'title',
                'due_at',
                'status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Agenda de hoje consolidada
        |--------------------------------------------------------------------------
        |
        | Reúne:
        | - tarefas pendentes com vencimento no dia;
        | - prazos pendentes com vencimento no dia;
        | - compromissos agendados ainda restantes no dia.
        |
        | Tarefas e prazos vencidos mais cedo no próprio dia continuam
        | aparecendo enquanto permanecerem pendentes.
        |
        */

        $todayStart =
            now()
            ->copy()
            ->startOfDay();

        $todayEnd =
            now()
            ->copy()
            ->endOfDay();

        $todayTasks =
            FolderTask::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'pending',
            )
            ->whereNotNull(
                'due_at'
            )
            ->whereBetween(
                'due_at',
                [
                    $todayStart,
                    $todayEnd,
                ],
            )
            ->with([
                'folder:id,name,process_number',
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
                'title',
                'priority',
                'due_at',
                'status',
            ])
            ->map(
                fn(FolderTask $task): array => [
                    'kind' =>
                    'task',

                    'id' =>
                    $task->id,

                    'title' =>
                    $task->title,

                    'scheduled_at' =>
                    $task->due_at,

                    'priority' =>
                    $task->priority,

                    'folder' =>
                    $this->serializeActivityFolder(
                        $task->folder,
                    ),
                ]
            );

        $todayDeadlines =
            FolderDeadline::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'pending',
            )
            ->whereNotNull(
                'due_at'
            )
            ->whereBetween(
                'due_at',
                [
                    $todayStart,
                    $todayEnd,
                ],
            )
            ->with([
                'folder:id,name,process_number',
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
                'title',
                'due_at',
                'status',
            ])
            ->map(
                fn(FolderDeadline $deadline): array => [
                    'kind' =>
                    'deadline',

                    'id' =>
                    $deadline->id,

                    'title' =>
                    $deadline->title,

                    'scheduled_at' =>
                    $deadline->due_at,

                    'folder' =>
                    $this->serializeActivityFolder(
                        $deadline->folder,
                    ),
                ]
            );

        $todayEvents =
            FolderEvent::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'scheduled',
            )
            ->where(
                'starts_at',
                '>=',
                now(),
            )
            ->where(
                'starts_at',
                '<=',
                $todayEnd,
            )
            ->with([
                'folder:id,name,process_number',
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
                'type',
                'title',
                'starts_at',
                'ends_at',
                'location',
                'status',
            ])
            ->map(
                fn(FolderEvent $event): array => [
                    'kind' =>
                    'event',

                    'id' =>
                    $event->id,

                    'title' =>
                    $event->title,

                    'scheduled_at' =>
                    $event->starts_at,

                    'type' =>
                    $event->type,

                    'location' =>
                    $event->location,

                    'folder' =>
                    $this->serializeActivityFolder(
                        $event->folder,
                    ),
                ]
            );

        $todayAgenda =
            collect()
            ->concat(
                $todayTasks
            )
            ->concat(
                $todayDeadlines
            )
            ->concat(
                $todayEvents
            )
            ->sortBy(
                fn(array $item) =>
                $item['scheduled_at']
                    ?->getTimestamp()
                    ?? PHP_INT_MAX
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Meu trabalho - Tarefas pendentes
        |--------------------------------------------------------------------------
        */

        $myPendingTasks =
            FolderTask::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'user_id',
                $user->id,
            )
            ->where(
                'status',
                'pending',
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->orderByRaw(
                'CASE WHEN due_at IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy(
                'due_at'
            )
            ->orderByRaw(
                "
                CASE priority
                    WHEN 'high' THEN 0
                    WHEN 'medium' THEN 1
                    WHEN 'low' THEN 2
                    ELSE 3
                END
                "
            )
            ->orderBy(
                'id'
            )
            ->limit(5)
            ->get([
                'id',
                'folder_id',
                'title',
                'priority',
                'due_at',
                'status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Meu trabalho - Prazos pendentes
        |--------------------------------------------------------------------------
        */

        $myPendingDeadlines =
            FolderDeadline::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'user_id',
                $user->id,
            )
            ->where(
                'status',
                'pending',
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->orderByRaw(
                'CASE WHEN due_at IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy(
                'due_at'
            )
            ->orderBy(
                'id'
            )
            ->limit(5)
            ->get([
                'id',
                'folder_id',
                'title',
                'due_at',
                'status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Meu trabalho - Próximos compromissos
        |--------------------------------------------------------------------------
        */

        $myUpcomingEvents =
            FolderEvent::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'user_id',
                $user->id,
            )
            ->where(
                'status',
                'scheduled',
            )
            ->where(
                'starts_at',
                '>=',
                now(),
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->orderBy(
                'starts_at'
            )
            ->orderBy(
                'id'
            )
            ->limit(5)
            ->get([
                'id',
                'folder_id',
                'type',
                'title',
                'starts_at',
                'ends_at',
                'location',
                'status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Atividade recente
        |--------------------------------------------------------------------------
        |
        | Reúne tarefas, prazos e compromissos concluídos em uma única
        | linha do tempo, ordenada por completed_at decrescente.
        |
        */

        $recentTaskActivity =
            FolderTask::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'completed',
            )
            ->whereNotNull(
                'completed_at'
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->orderByDesc(
                'completed_at'
            )
            ->limit(10)
            ->get([
                'id',
                'folder_id',
                'title',
                'completed_at',
            ])
            ->map(
                fn(FolderTask $task): array => [
                    'type' =>
                    'task',

                    'id' =>
                    $task->id,

                    'title' =>
                    $task->title,

                    'completed_at' =>
                    $task->completed_at,

                    'folder' =>
                    $this->serializeActivityFolder(
                        $task->folder,
                    ),
                ]
            );

        $recentDeadlineActivity =
            FolderDeadline::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'completed',
            )
            ->whereNotNull(
                'completed_at'
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->orderByDesc(
                'completed_at'
            )
            ->limit(10)
            ->get([
                'id',
                'folder_id',
                'title',
                'completed_at',
            ])
            ->map(
                fn(FolderDeadline $deadline): array => [
                    'type' =>
                    'deadline',

                    'id' =>
                    $deadline->id,

                    'title' =>
                    $deadline->title,

                    'completed_at' =>
                    $deadline->completed_at,

                    'folder' =>
                    $this->serializeActivityFolder(
                        $deadline->folder,
                    ),
                ]
            );

        $recentEventActivity =
            FolderEvent::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->where(
                'status',
                'completed',
            )
            ->whereNotNull(
                'completed_at'
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->orderByDesc(
                'completed_at'
            )
            ->limit(10)
            ->get([
                'id',
                'folder_id',
                'title',
                'completed_at',
            ])
            ->map(
                fn(FolderEvent $event): array => [
                    'type' =>
                    'event',

                    'id' =>
                    $event->id,

                    'title' =>
                    $event->title,

                    'completed_at' =>
                    $event->completed_at,

                    'folder' =>
                    $this->serializeActivityFolder(
                        $event->folder,
                    ),
                ]
            );

        $recentActivity =
            collect()
            ->concat(
                $recentTaskActivity
            )
            ->concat(
                $recentDeadlineActivity
            )
            ->concat(
                $recentEventActivity
            )
            ->sortByDesc(
                fn(array $activity) =>
                $activity['completed_at']
                    ?->getTimestamp()
                    ?? 0
            )
            ->take(10)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'summary' => [
                'clients' =>
                $organization
                    ->clients()
                    ->count(),

                'folders' =>
                $organization
                    ->folders()
                    ->count(),

                'active_members' =>
                $organization
                    ->users()
                    ->wherePivot(
                        'status',
                        'active',
                    )
                    ->count(),

                'pending_tasks' =>
                $pendingTasks,

                'pending_deadlines' =>
                $pendingDeadlines,

                'upcoming_events' =>
                $upcomingEvents,

                'overdue_tasks' =>
                $overdueTasks,

                'overdue_deadlines' =>
                $overdueDeadlines,
            ],

            'attention' => [
                'overdue_tasks' =>
                $attentionOverdueTasks,

                'overdue_deadlines' =>
                $attentionOverdueDeadlines,
            ],

            'today_agenda' =>
            $todayAgenda,

            'my_work' => [
                'pending_tasks' =>
                $myPendingTasks,

                'pending_deadlines' =>
                $myPendingDeadlines,

                'upcoming_events' =>
                $myUpcomingEvents,
            ],

            'recent_activity' =>
            $recentActivity,

            'recent_folders' =>
            $recentFolders,
        ]);
    }

    private function serializeActivityFolder(
        mixed $folder,
    ): ?array {
        if (!$folder) {
            return null;
        }

        return [
            'id' =>
            $folder->id,

            'name' =>
            $folder->name,

            'process_number' =>
            $folder->process_number,
        ];
    }
}
