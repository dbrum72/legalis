<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceInstallmentRequest;
use App\Http\Requests\InvoiceRequest;
use App\Models\Client;
use App\Models\FeeAgreement;
use App\Models\Folder;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with(['client:id,name', 'folder:id,name', 'payments'])
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByRaw("CASE WHEN status IN ('open', 'partial') AND due_on < CURRENT_DATE THEN 0 ELSE 1 END")
            ->orderBy('due_on')
            ->latest('id')
            ->get();

        return response()->json($invoices);
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->validateRelations($data);
        $discount = $data['discount_cents'] ?? 0;
        if ($discount > $data['subtotal_cents']) {
            throw ValidationException::withMessages(['discount_cents' => 'O desconto não pode superar o subtotal.']);
        }
        $count = $data['installment_count'] ?? 1;
        $interval = $data['installment_interval_months'] ?? 1;
        unset($data['installment_count'], $data['installment_interval_months']);

        $installments = DB::transaction(function () use ($data, $discount, $count, $interval) {
            $firstId = (int) Invoice::withoutGlobalScopes()->max('id') + 1;
            $identifier = sprintf('COB-%d-%06d', now()->year, $firstId);
            $subtotals = $this->splitCents($data['subtotal_cents'], $count);
            $discounts = $this->splitCents($discount, $count);
            $firstDueOn = Carbon::parse($data['due_on']);
            $created = collect();

            foreach (range(1, $count) as $number) {
                $subtotal = $subtotals[$number - 1];
                $installmentDiscount = $discounts[$number - 1];
                $total = $subtotal - $installmentDiscount;
                $nextId = (int) Invoice::withoutGlobalScopes()->max('id') + 1;

                $created->push(Invoice::query()->create(array_merge($data, [
                    'number' => sprintf('%d-%06d', now()->year, $nextId),
                    'charge_identifier' => $identifier,
                    'installment_number' => $number,
                    'installment_count' => $count,
                    'due_on' => $firstDueOn->copy()->addMonthsNoOverflow(($number - 1) * $interval)->toDateString(),
                    'status' => $data['status'] ?? 'open',
                    'issued_on' => $data['issued_on'] ?? now()->toDateString(),
                    'subtotal_cents' => $subtotal,
                    'discount_cents' => $installmentDiscount,
                    'total_cents' => $total,
                    'paid_cents' => 0,
                    'balance_cents' => $total,
                ])));
            }

            return $created;
        });

        $serializedInstallments = $installments
            ->map(fn (Invoice $item) => $item->load(['client:id,name', 'folder:id,name'])->toArray());
        $invoice = $installments->first()->load(['client:id,name', 'folder:id,name']);
        $invoice->setAttribute('installments', $serializedInstallments);

        return response()->json($invoice, 201);
    }

    public function storeInstallment(InvoiceInstallmentRequest $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validated();
        $discount = $data['discount_cents'] ?? 0;
        if ($discount > $data['subtotal_cents']) {
            throw ValidationException::withMessages(['discount_cents' => 'O desconto não pode superar o subtotal.']);
        }

        $installment = DB::transaction(function () use ($invoice, $data, $discount) {
            $identifier = $invoice->charge_identifier ?: $invoice->number;
            $group = Invoice::query()->where('charge_identifier', $identifier)->lockForUpdate();
            $number = (int) $group->max('installment_number') + 1;
            $total = $data['subtotal_cents'] - $discount;
            $nextId = (int) Invoice::withoutGlobalScopes()->max('id') + 1;

            $created = Invoice::query()->create([
                'organization_id' => $invoice->organization_id,
                'folder_id' => $invoice->folder_id,
                'client_id' => $invoice->client_id,
                'fee_agreement_id' => $invoice->fee_agreement_id,
                'number' => sprintf('%d-%06d', now()->year, $nextId),
                'charge_identifier' => $identifier,
                'installment_number' => $number,
                'installment_count' => $number,
                'issued_on' => now()->toDateString(),
                'due_on' => $data['due_on'],
                'status' => 'open',
                'subtotal_cents' => $data['subtotal_cents'],
                'discount_cents' => $discount,
                'total_cents' => $total,
                'paid_cents' => 0,
                'balance_cents' => $total,
                'notes' => $data['notes'] ?? $invoice->notes,
            ]);

            Invoice::query()->where('charge_identifier', $identifier)->update(['installment_count' => $number]);

            return $created;
        });

        return response()->json($installment->refresh()->load(['client:id,name', 'folder:id,name', 'payments']), 201);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            throw ValidationException::withMessages(['invoice' => 'Uma cobrança paga ou cancelada não pode ser alterada.']);
        }
        $data = array_merge($invoice->toArray(), $request->validated());
        $this->validateRelations($data);
        if ($data['discount_cents'] > $data['subtotal_cents']) {
            throw ValidationException::withMessages(['discount_cents' => 'O desconto não pode superar o subtotal.']);
        }
        $total = $data['subtotal_cents'] - $data['discount_cents'];
        if ($total < $invoice->paid_cents) {
            throw ValidationException::withMessages(['subtotal_cents' => 'O total não pode ser inferior ao valor já pago.']);
        }
        $invoice->update(array_merge($request->validated(), [
            'total_cents' => $total,
            'balance_cents' => $total - $invoice->paid_cents,
        ]));

        return response()->json($invoice->refresh()->load(['client:id,name', 'folder:id,name', 'payments']));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->payments()->exists()) {
            throw ValidationException::withMessages(['invoice' => 'A cobrança possui pagamentos e não pode ser excluída.']);
        }
        DB::transaction(function () use ($invoice): void {
            $identifier = $invoice->charge_identifier;
            $invoice->delete();

            if (! $identifier) {
                return;
            }

            $remaining = Invoice::query()
                ->where('charge_identifier', $identifier)
                ->orderBy('installment_number')
                ->lockForUpdate()
                ->get();
            $count = $remaining->count();

            foreach ($remaining as $index => $installment) {
                $installment->update([
                    'installment_number' => $index + 1,
                    'installment_count' => $count,
                ]);
            }
        });

        return response()->json(null, 204);
    }

    private function validateRelations(array $data): void
    {
        abort_unless(Client::query()->whereKey($data['client_id'])->exists(), 404);
        if (! empty($data['folder_id'])) {
            $folder = Folder::query()->whereKey($data['folder_id'])->firstOrFail();
            abort_unless($folder->clients()->whereKey($data['client_id'])->exists(), 422);
        }
        if (! empty($data['fee_agreement_id'])) {
            $agreement = FeeAgreement::query()->whereKey($data['fee_agreement_id'])->firstOrFail();
            abort_unless((int) $agreement->client_id === (int) $data['client_id'], 422);
            abort_unless(empty($data['folder_id']) || (int) $agreement->folder_id === (int) $data['folder_id'], 422);
        }
    }

    private function splitCents(int $amount, int $count): array
    {
        $base = intdiv($amount, $count);
        $remainder = $amount % $count;

        return array_map(
            fn (int $index) => $base + ($index < $remainder ? 1 : 0),
            range(0, $count - 1),
        );
    }
}
