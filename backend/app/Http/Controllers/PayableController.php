<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelPaymentRequest;
use App\Http\Requests\PayableRequest;
use App\Models\Payable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Payable::query()->select('payables.*')->selectSub(
            \Illuminate\Support\Facades\DB::table('payable_recurrence_occurrences')->select('payable_recurrence_id')->whereColumn('payable_id', 'payables.id'), 'recurrence_id'
        )->with(['payments.recordedBy:id,name', 'payments.cancelledBy:id,name', 'financialCategory', 'costCenter', 'folder:id,name', 'client:id,name']);
        foreach (['category_id', 'cost_center_id', 'folder_id', 'client_id'] as $field) {
            if ($request->filled($field)) {
                $q->where($field, $request->input($field));
            }
        } if ($request->filled('supplier')) {
            $q->where('supplier', 'like', '%'.$request->string('supplier')->trim().'%');
        } if ($request->filled('status')) {
            if ($request->status === 'due_soon') {
                $q->whereIn('status', ['open', 'partial'])->where('balance_cents', '>', 0)->whereBetween('due_on', [today('America/Sao_Paulo')->toDateString(), today('America/Sao_Paulo')->addDays(7)->toDateString()]);
            } elseif ($request->status === 'overdue') {
                $q->whereIn('status', ['open', 'partial'])->whereDate('due_on', '<', today());
            } else {
                $q->where('status', $request->status);
            }
        } if ($request->filled('due_from')) {
            $q->whereDate('due_on', '>=', $request->date('due_from'));
        } if ($request->filled('due_to')) {
            $q->whereDate('due_on', '<=', $request->date('due_to'));
        }

        return response()->json($q->orderByRaw("CASE WHEN status IN ('open','partial') AND due_on < CURRENT_DATE THEN 0 ELSE 1 END")->orderBy('due_on')->get());
    }

    public function store(PayableRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json(Payable::query()->create($data + ['balance_cents' => $data['amount_cents'], 'status' => 'open']), 201);
    }

    public function update(PayableRequest $request, Payable $payable): JsonResponse
    {
        abort_if($payable->paid_cents > 0, 422, 'Contas com pagamentos não podem ser alteradas.');
        $data = $request->validated();
        $payable->update($data + ['balance_cents' => $data['amount_cents']]);

        return response()->json($payable->refresh()->load(['financialCategory', 'costCenter', 'folder:id,name', 'client:id,name']));
    }

    public function destroy(Payable $payable): JsonResponse
    {
        abort_if(\Illuminate\Support\Facades\DB::table('payable_recurrence_occurrences')->where('payable_id', $payable->id)->exists(), 422, 'Contas recorrentes devem ser canceladas, preservando o histórico da geração.');
        abort_if($payable->payments()->exists(), 422, 'Contas com pagamentos não podem ser excluídas.');
        $payable->delete();

        return response()->json(null, 204);
    }

    public function cancel(CancelPaymentRequest $request, Payable $payable): JsonResponse
    {
        abort_if($payable->payments()->whereNull('cancelled_at')->exists(), 422, 'Uma conta com pagamentos ativos não pode ser cancelada.');
        $payable->update(['status' => 'cancelled', 'balance_cents' => 0, 'cancellation_reason' => $request->string('reason')->trim()->toString(), 'cancelled_at' => now()]);

        return response()->json($payable->refresh()->load(['financialCategory', 'costCenter', 'folder:id,name', 'client:id,name']));
    }
}
