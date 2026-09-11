<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelPaymentRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
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
            $paid = (int) $lockedInvoice->payments()->whereNull('cancelled_at')->sum('amount_cents');
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

    public function cancel(CancelPaymentRequest $request, Invoice $invoice, Payment $payment): JsonResponse
    {
        if ($payment->invoice_id !== $invoice->id) {
            abort(404);
        }

        $payment = DB::transaction(function () use ($request, $invoice, $payment): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedPayment->cancelled_at !== null) {
                throw ValidationException::withMessages(['payment' => 'O pagamento já está cancelado.']);
            }

            $lockedPayment->update([
                'cancelled_by' => $request->user('api')?->id,
                'cancellation_reason' => $request->string('reason')->trim()->toString(),
                'cancelled_at' => now(),
            ]);

            $paid = (int) $lockedInvoice->payments()->whereNull('cancelled_at')->sum('amount_cents');
            $balance = $lockedInvoice->total_cents - $paid;
            $lockedInvoice->update([
                'paid_cents' => $paid,
                'balance_cents' => $balance,
                'status' => $paid > 0 ? 'partial' : 'open',
            ]);

            return $lockedPayment;
        });

        return response()->json($payment->load(['recordedBy:id,name', 'cancelledBy:id,name']));
    }
}
