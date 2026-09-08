<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeEntryRequest;
use App\Models\Folder;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TimeEntryController extends Controller
{
    public function index(Folder $folder): JsonResponse
    {
        return response()->json($folder->timeEntries()->with('user:id,name')->latest('worked_on')->latest('id')->get());
    }

    public function store(TimeEntryRequest $request, Folder $folder): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user('api');
        $defaultRate = $folder->feeAgreements()
            ->where('status', 'active')
            ->whereNotNull('hourly_rate_cents')
            ->latest('id')
            ->value('hourly_rate_cents') ?? 0;
        $entry = $folder->timeEntries()->create(array_merge($data, [
            'user_id' => $user?->id,
            'hourly_rate_cents' => $user?->can('finance.manage')
                ? ($data['hourly_rate_cents'] ?? $defaultRate)
                : $defaultRate,
            'billable' => $data['billable'] ?? true,
            'status' => $data['status'] ?? 'open',
        ]));

        return response()->json($entry->load('user:id,name'), 201);
    }

    public function update(TimeEntryRequest $request, Folder $folder, TimeEntry $timeEntry): JsonResponse
    {
        $this->ensureEditable($folder, $timeEntry);
        $this->ensureOwnerOrSupervisor($request->user('api'), $timeEntry);
        $data = $request->validated();
        if (! $request->user('api')?->can('finance.manage')) {
            unset($data['hourly_rate_cents']);
        }
        $timeEntry->update($data);

        return response()->json($timeEntry->refresh()->load('user:id,name'));
    }

    public function destroy(Folder $folder, TimeEntry $timeEntry): JsonResponse
    {
        $this->ensureEditable($folder, $timeEntry);
        $timeEntry->delete();

        return response()->json(null, 204);
    }

    private function ensureEditable(Folder $folder, TimeEntry $entry): void
    {
        abort_unless((int) $entry->folder_id === (int) $folder->id, 404);
        if ($entry->invoice_id !== null || $entry->status === 'billed') {
            throw ValidationException::withMessages(['time_entry' => 'Um apontamento faturado não pode ser alterado.']);
        }
    }

    private function ensureOwnerOrSupervisor($user, TimeEntry $entry): void
    {
        abort_unless(
            (int) $entry->user_id === (int) $user?->id
            || $user?->can('time-entries.delete'),
            403,
        );
    }
}
