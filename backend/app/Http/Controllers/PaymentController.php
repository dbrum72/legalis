<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function store(PaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $payment = DB::transaction(function () use ($request, $invoice) {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $amount = $request->integer('amount_cents');
            if (! in_array($lockedInvoice->status, ['open', 'partial'], true) || $amount > $lockedInvoice->balance_cents) {
                throw ValidationException::withMessages(['amount_cents' => 'O pagamento deve respeitar o saldo em aberto.']);
            }
            $payment = $lockedInvoice->payments()->create(array_merge($request->validated(), [
                'recorded_by' => $request->user('api')?->id,
            ]));
            $paid = (int) $lockedInvoice->payments()->sum('amount_cents');
            $balance = $lockedInvoice->total_cents - $paid;
            $lockedInvoice->update([
                'paid_cents' => $paid,
                'balance_cents' => $balance,
                'status' => $balance === 0 ? 'paid' : 'partial',
            ]);

            return $payment;
        });

        return response()->json($payment->load('recordedBy:id,name'), 201);
    }
}
