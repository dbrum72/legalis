<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelPaymentRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Requests\SendPaymentReceiptRequest;
use App\Mail\PaymentReceiptMail;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function receipt(Invoice $invoice, Payment $payment): Response
    {
        abort_unless((int) $payment->invoice_id === (int) $invoice->id, 404);
        abort_if($payment->cancelled_at !== null, 409, 'Pagamentos cancelados não possuem comprovante válido.');

        $payment->load(['recordedBy:id,name', 'invoice.organization:id,name', 'invoice.client:id,name,document', 'invoice.folder:id,name']);

        return response()->view('financial.payment-receipt', compact('payment'));
    }

    public function sendReceipt(SendPaymentReceiptRequest $request, Invoice $invoice, Payment $payment): JsonResponse
    {
        abort_unless((int) $payment->invoice_id === (int) $invoice->id, 404);
        abort_if($payment->cancelled_at !== null, 409, 'Pagamentos cancelados não possuem comprovante válido.');
        $payment->load(['invoice.organization:id,name', 'invoice.client:id,name']);
        Mail::to($request->validated('recipient'))->send(new PaymentReceiptMail($payment));
        $delivery = $payment->receiptDeliveries()->create(['sent_by' => $request->user('api')?->id, 'recipient' => $request->validated('recipient'), 'sent_at' => now()]);

        return response()->json($delivery->load('sentBy:id,name'), 201);
    }

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
