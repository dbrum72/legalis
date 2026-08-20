<?php

namespace App\Http\Controllers;

use App\Models\FolderDeadline;
use App\Models\FolderEvent;
use App\Models\FolderTask;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AgendaController extends Controller
{
    public function index(
        Request $request,
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Validação
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'start' => [
                    'required',
                    'date_format:Y-m-d',
                ],

                'end' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:start',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Organização atual
        |--------------------------------------------------------------------------
        */

        $organization =
            $currentOrganization->get();

        /*
        |--------------------------------------------------------------------------
        | Período
        |--------------------------------------------------------------------------
        |
        | Os parâmetros representam dias civis.
        |
        | Para garantir que todo o dia final seja incluído, convertemos:
        |
        | start => início do dia
        | end   => fim do dia
        |
        */

        $start =
            Carbon::createFromFormat(
                'Y-m-d',
                $validated['start'],
                config('app.timezone'),
            )
            ->startOfDay();

        $end =
            Carbon::createFromFormat(
                'Y-m-d',
                $validated['end'],
                config('app.timezone'),
            )
            ->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Pastas da organização atual
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
        | Tarefas
        |--------------------------------------------------------------------------
        |
        | A data temporal da tarefa é due_at.
        |
        | Tarefas pendentes ou concluídas permanecem representadas no
        | calendário desde que possuam vencimento dentro do intervalo.
        |
        */

        $tasks =
            FolderTask::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->whereNotNull(
                'due_at'
            )
            ->whereBetween(
                'due_at',
                [
                    $start,
                    $end,
                ],
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->get([
                'id',
                'folder_id',
                'title',
                'priority',
                'due_at',
                'status',
                'completed_at',
            ])
            ->map(
                fn(FolderTask $task): array =>
                $this->serializeTask(
                    $task
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Prazos
        |--------------------------------------------------------------------------
        |
        | A data temporal do prazo também é due_at.
        |
        */

        $deadlines =
            FolderDeadline::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->whereNotNull(
                'due_at'
            )
            ->whereBetween(
                'due_at',
                [
                    $start,
                    $end,
                ],
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->get([
                'id',
                'folder_id',
                'title',
                'due_at',
                'status',
                'completed_at',
            ])
            ->map(
                fn(FolderDeadline $deadline): array =>
                $this->serializeDeadline(
                    $deadline
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Compromissos
        |--------------------------------------------------------------------------
        |
        | O compromisso pertence ao calendário pela sua data de início.
        |
        */

        $events =
            FolderEvent::query()
            ->whereIn(
                'folder_id',
                clone $organizationFolderIds,
            )
            ->whereNotNull(
                'starts_at'
            )
            ->whereBetween(
                'starts_at',
                [
                    $start,
                    $end,
                ],
            )
            ->with([
                'folder:id,name,process_number',
            ])
            ->get([
                'id',
                'folder_id',
                'type',
                'title',
                'starts_at',
                'ends_at',
                'location',
                'status',
                'completed_at',
            ])
            ->map(
                fn(FolderEvent $event): array =>
                $this->serializeEvent(
                    $event
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Consolidação
        |--------------------------------------------------------------------------
        |
        | A API expõe os três recursos como uma única sequência temporal.
        |
        | Todos utilizam a propriedade starts_at no contrato consolidado:
        |
        | task     => due_at
        | deadline => due_at
        | event    => starts_at
        |
        */

        $items =
            collect()
            ->concat(
                $tasks
            )
            ->concat(
                $deadlines
            )
            ->concat(
                $events
            )
            ->sort(
                function (
                    array $left,
                    array $right,
                ): int {
                    $leftTimestamp =
                        Carbon::parse(
                            $left['starts_at']
                        )
                        ->getTimestamp();

                    $rightTimestamp =
                        Carbon::parse(
                            $right['starts_at']
                        )
                        ->getTimestamp();

                    if (
                        $leftTimestamp ===
                        $rightTimestamp
                    ) {
                        $typeOrder = [
                            'deadline' => 0,
                            'event' => 1,
                            'task' => 2,
                        ];

                        $leftTypeOrder =
                            $typeOrder[$left['type']] ?? 99;

                        $rightTypeOrder =
                            $typeOrder[$right['type']] ?? 99;

                        if (
                            $leftTypeOrder ===
                            $rightTypeOrder
                        ) {
                            return
                                $left['id']
                                <=>
                                $right['id'];
                        }

                        return
                            $leftTypeOrder
                            <=>
                            $rightTypeOrder;
                    }

                    return
                        $leftTimestamp
                        <=>
                        $rightTimestamp;
                }
            )
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'period' => [
                'start' =>
                $validated['start'],

                'end' =>
                $validated['end'],
            ],

            'items' =>
            $items,
        ]);
    }

    private function serializeTask(
        FolderTask $task,
    ): array {
        return [
            'type' =>
            'task',

            'id' =>
            $task->id,

            'title' =>
            $task->title,

            /*
            |--------------------------------------------------------------------------
            | Data unificada
            |--------------------------------------------------------------------------
            */

            'starts_at' =>
            $task->due_at,

            'ends_at' =>
            null,

            /*
            |--------------------------------------------------------------------------
            | Dados específicos
            |--------------------------------------------------------------------------
            */

            'priority' =>
            $task->priority,

            'event_type' =>
            null,

            'location' =>
            null,

            'status' =>
            $task->status,

            'completed_at' =>
            $task->completed_at,

            /*
            |--------------------------------------------------------------------------
            | Pasta
            |--------------------------------------------------------------------------
            */

            'folder' =>
            $this->serializeFolder(
                $task->folder
            ),
        ];
    }

    private function serializeDeadline(
        FolderDeadline $deadline,
    ): array {
        return [
            'type' =>
            'deadline',

            'id' =>
            $deadline->id,

            'title' =>
            $deadline->title,

            /*
            |--------------------------------------------------------------------------
            | Data unificada
            |--------------------------------------------------------------------------
            */

            'starts_at' =>
            $deadline->due_at,

            'ends_at' =>
            null,

            /*
            |--------------------------------------------------------------------------
            | Dados específicos
            |--------------------------------------------------------------------------
            */

            'priority' =>
            null,

            'event_type' =>
            null,

            'location' =>
            null,

            'status' =>
            $deadline->status,

            'completed_at' =>
            $deadline->completed_at,

            /*
            |--------------------------------------------------------------------------
            | Pasta
            |--------------------------------------------------------------------------
            */

            'folder' =>
            $this->serializeFolder(
                $deadline->folder
            ),
        ];
    }

    private function serializeEvent(
        FolderEvent $event,
    ): array {
        return [
            'type' =>
            'event',

            'id' =>
            $event->id,

            'title' =>
            $event->title,

            /*
            |--------------------------------------------------------------------------
            | Data
            |--------------------------------------------------------------------------
            */

            'starts_at' =>
            $event->starts_at,

            'ends_at' =>
            $event->ends_at,

            /*
            |--------------------------------------------------------------------------
            | Dados específicos
            |--------------------------------------------------------------------------
            */

            'priority' =>
            null,

            'event_type' =>
            $event->type,

            'location' =>
            $event->location,

            'status' =>
            $event->status,

            'completed_at' =>
            $event->completed_at,

            /*
            |--------------------------------------------------------------------------
            | Pasta
            |--------------------------------------------------------------------------
            */

            'folder' =>
            $this->serializeFolder(
                $event->folder
            ),
        ];
    }

    private function serializeFolder(
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
