<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelInvoiceRequest;
use App\Http\Requests\FolderBillingRequest;
use App\Http\Requests\InvoiceInstallmentRequest;
use App\Http\Requests\InvoiceRequest;
use App\Models\Client;
use App\Models\Expense;
use App\Models\FeeAgreement;
use App\Models\Folder;
use App\Models\Invoice;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function cancel(CancelInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages(['invoice' => 'A cobrança já está cancelada.']);
        }
        if ($invoice->payments()->exists() || $invoice->paid_cents > 0) {
            throw ValidationException::withMessages(['invoice' => 'Uma cobrança com pagamentos não pode ser cancelada.']);
        }

        DB::transaction(function () use ($invoice, $request): void {
            $invoice->timeEntries()->update(['invoice_id' => null, 'status' => 'open']);
            $invoice->expenses()->update(['invoice_id' => null, 'status' => 'open']);
            $invoice->update([
                'status' => 'cancelled',
                'balance_cents' => 0,
                'cancellation_reason' => $request->validated('reason'),
                'cancelled_at' => now(),
            ]);
        });

        return response()->json($invoice->refresh()->load(['client:id,name', 'folder:id,name']));
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load([
            'client:id,name,email',
            'folder:id,name',
            'feeAgreement.client:id,name',
            'timeEntries.user:id,name',
            'expenses.user:id,name',
            'payments.recordedBy:id,name',
            'payments.cancelledBy:id,name',
            'payments.receiptDeliveries.sentBy:id,name',
            'reminders.sentBy:id,name',
        ]));
    }

    public function storeFromFolderItems(FolderBillingRequest $request, Folder $folder): JsonResponse
    {
        $data = $request->validated();
        $this->validateRelations($data + ['folder_id' => $folder->id]);
        $timeEntryIds = $data['time_entry_ids'] ?? [];
        $expenseIds = $data['expense_ids'] ?? [];

        if ($timeEntryIds === [] && $expenseIds === []) {
            throw ValidationException::withMessages(['items' => 'Selecione ao menos um lançamento para faturar.']);
        }

        $invoice = DB::transaction(function () use ($data, $folder, $timeEntryIds, $expenseIds): Invoice {
            $timeEntries = TimeEntry::query()->where('folder_id', $folder->id)->whereIn('id', $timeEntryIds)
                ->whereNull('invoice_id')->where('status', 'open')->where('billable', true)->lockForUpdate()->get();
            $expenses = Expense::query()->where('folder_id', $folder->id)->whereIn('id', $expenseIds)
                ->whereNull('invoice_id')->where('status', 'open')->where('reimbursable', true)->lockForUpdate()->get();

            if ($timeEntries->count() !== count($timeEntryIds) || $expenses->count() !== count($expenseIds)) {
                throw ValidationException::withMessages(['items' => 'Um ou mais lançamentos não estão disponíveis para faturamento.']);
            }

            $subtotal = $timeEntries->sum(fn (TimeEntry $entry) => $entry->billableAmountCents())
                + $expenses->sum('amount_cents');
            $discount = $data['discount_cents'] ?? 0;
            if ($subtotal < 1 || $discount > $subtotal) {
                throw ValidationException::withMessages(['discount_cents' => 'O desconto não pode superar o valor faturável.']);
            }

            $nextId = (int) Invoice::withoutGlobalScopes()->max('id') + 1;
            $identifier = sprintf('COB-%d-%06d', now()->year, $nextId);
            $invoice = Invoice::query()->create([
                'organization_id' => $folder->organization_id,
                'folder_id' => $folder->id,
                'client_id' => $data['client_id'],
                'fee_agreement_id' => $data['fee_agreement_id'] ?? null,
                'number' => sprintf('%d-%06d', now()->year, $nextId),
                'charge_identifier' => $identifier,
                'installment_number' => 1,
                'installment_count' => 1,
                'issued_on' => now()->toDateString(),
                'due_on' => $data['due_on'],
                'status' => 'open',
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'total_cents' => $subtotal - $discount,
                'paid_cents' => 0,
                'balance_cents' => $subtotal - $discount,
                'notes' => $data['notes'] ?? null,
            ]);

            TimeEntry::query()->whereKey($timeEntries->modelKeys())->update(['invoice_id' => $invoice->id, 'status' => 'billed']);
            Expense::query()->whereKey($expenses->modelKeys())->update(['invoice_id' => $invoice->id, 'status' => 'billed']);

            return $invoice;
        });

        return response()->json($invoice->load(['client:id,name', 'folder:id,name']), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'client_id' => ['nullable', 'integer'],
            'transaction' => ['nullable', 'string', 'max:40'],
            'month' => ['nullable', 'date_format:Y-m'],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date', 'after_or_equal:due_from'],
            'aging' => ['nullable', Rule::in(['current', 'days_1_30', 'days_31_60', 'days_61_90', 'over_90'])],
            'status' => ['nullable', Rule::in(['pending', 'overdue', 'draft', 'paid', 'cancelled'])],
            'sort' => ['nullable', Rule::in(['priority', 'due_asc', 'due_desc', 'balance_desc', 'recent'])],
            'contact' => ['nullable', Rule::in(['without_reminder', 'reminded'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $invoices = Invoice::query()
            ->with(['client:id,name,email', 'folder:id,name', 'payments'])
            ->withCount(['reminders as reminders_count' => fn ($reminders) => $reminders->where('status', 'sent')])
            ->withMax(['reminders as last_reminder_at' => fn ($reminders) => $reminders->where('status', 'sent')], 'sent_at')
            ->when($filters['status'] ?? null, function ($query, $status): void {
                match ($status) {
                    'pending' => $query->whereIn('status', ['open', 'partial']),
                    'overdue' => $query->whereIn('status', ['open', 'partial'])->whereDate('due_on', '<', today()),
                    default => $query->where('status', $status),
                };
            })
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['transaction'] ?? null, function ($query, $transaction): void {
                $query->where(function ($query) use ($transaction): void {
                    $query->where('charge_identifier', 'like', "%{$transaction}%")
                        ->orWhere('number', 'like', "%{$transaction}%")
                        ->orWhereHas('payments', fn ($payments) => $payments->where('reference', 'like', "%{$transaction}%"));
                });
            })
            ->when($filters['month'] ?? null, function ($query, $month): void {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $query->whereBetween('due_on', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
            })
            ->when($filters['due_from'] ?? null, fn ($query, $date) => $query->whereDate('due_on', '>=', $date))
            ->when($filters['due_to'] ?? null, fn ($query, $date) => $query->whereDate('due_on', '<=', $date))
            ->when($filters['aging'] ?? null, function ($query, $aging): void {
                $query->whereIn('status', ['open', 'partial']);
                match ($aging) {
                    'current' => $query->whereDate('due_on', '>=', today()),
                    'days_1_30' => $query->whereBetween('due_on', [today()->subDays(30), today()->subDay()]),
                    'days_31_60' => $query->whereBetween('due_on', [today()->subDays(60), today()->subDays(31)]),
                    'days_61_90' => $query->whereBetween('due_on', [today()->subDays(90), today()->subDays(61)]),
                    'over_90' => $query->whereDate('due_on', '<=', today()->subDays(91)),
                };
            })
            ->when($filters['contact'] ?? null, function ($query, $contact): void {
                $query->whereIn('status', ['open', 'partial'])->whereDate('due_on', '<', today());
                if ($contact === 'without_reminder') {
                    $query->whereDoesntHave('reminders', fn ($reminders) => $reminders->where('status', 'sent'));
                } else {
                    $query->whereHas('reminders', fn ($reminders) => $reminders->where('status', 'sent'));
                }
            });

        match ($filters['sort'] ?? 'priority') {
            'due_asc' => $invoices->orderBy('due_on')->latest('id'),
            'due_desc' => $invoices->orderByDesc('due_on')->latest('id'),
            'balance_desc' => $invoices->orderByDesc('balance_cents')->orderBy('due_on'),
            'recent' => $invoices->latest('id'),
            default => $invoices->orderByRaw("CASE WHEN status IN ('open', 'partial') AND due_on < CURRENT_DATE THEN 0 ELSE 1 END")->orderBy('due_on')->latest('id'),
        };

        if ($filters['per_page'] ?? null) {
            return response()->json($invoices->paginate($filters['per_page']));
        }

        return response()->json($invoices->get());
    }

    public function export(Request $request): StreamedResponse
    {
        $invoices = $this->index($request)->getData(true);
        $filename = 'contas-a-receber-'.today()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($invoices): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Cobrança', 'Parcela', 'Cliente', 'Pasta', 'Vencimento', 'Situação', 'Subtotal (R$)', 'Desconto (R$)', 'Total (R$)', 'Pago (R$)', 'Saldo (R$)', 'Referências de pagamento', 'Último lembrete'], ';');

            foreach ($invoices as $invoice) {
                fputcsv($output, [
                    $invoice['charge_identifier'] ?? $invoice['number'],
                    ($invoice['installment_number'] ?? 1).'/'.($invoice['installment_count'] ?? 1),
                    $invoice['client']['name'] ?? '',
                    $invoice['folder']['name'] ?? 'Sem pasta',
                    Carbon::parse($invoice['due_on'])->format('d/m/Y'),
                    match ($invoice['status']) {
                        'draft' => 'Rascunho', 'open' => 'Em aberto', 'partial' => 'Parcial',
                        'paid' => 'Pago', 'cancelled' => 'Cancelado', default => $invoice['status'],
                    },
                    $this->csvMoney($invoice['subtotal_cents']),
                    $this->csvMoney($invoice['discount_cents']),
                    $this->csvMoney($invoice['total_cents']),
                    $this->csvMoney($invoice['paid_cents']),
                    $this->csvMoney($invoice['balance_cents']),
                    collect($invoice['payments'] ?? [])->whereNull('cancelled_at')->pluck('reference')->filter()->join(' | '),
                    isset($invoice['last_reminder_at']) ? Carbon::parse($invoice['last_reminder_at'])->format('d/m/Y H:i') : '',
                ], ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvMoney(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
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
