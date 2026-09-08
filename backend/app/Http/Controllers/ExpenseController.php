<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Folder $folder): JsonResponse
    {
        return response()->json($folder->expenses()->with('user:id,name')->latest('incurred_on')->latest('id')->get());
    }

    public function store(ExpenseRequest $request, Folder $folder): JsonResponse
    {
        $data = $request->validated();
        $expense = $folder->expenses()->create($data + [
            'user_id' => $request->user('api')?->id,
            'reimbursable' => $data['reimbursable'] ?? true,
            'status' => $data['status'] ?? 'open',
        ]);

        return response()->json($expense->load('user:id,name'), 201);
    }

    public function update(ExpenseRequest $request, Folder $folder, Expense $expense): JsonResponse
    {
        $this->ensureEditable($folder, $expense);
        $this->ensureOwnerOrSupervisor($request->user('api'), $expense);
        $expense->update($request->validated());

        return response()->json($expense->refresh()->load('user:id,name'));
    }

    public function destroy(Folder $folder, Expense $expense): JsonResponse
    {
        $this->ensureEditable($folder, $expense);
        $expense->delete();

        return response()->json(null, 204);
    }

    private function ensureEditable(Folder $folder, Expense $expense): void
    {
        abort_unless((int) $expense->folder_id === (int) $folder->id, 404);
        if ($expense->invoice_id !== null || $expense->status === 'billed') {
            throw ValidationException::withMessages(['expense' => 'Uma despesa faturada não pode ser alterada.']);
        }
    }

    private function ensureOwnerOrSupervisor($user, Expense $expense): void
    {
        abort_unless(
            (int) $expense->user_id === (int) $user?->id
            || $user?->can('expenses.delete'),
            403,
        );
    }
}
