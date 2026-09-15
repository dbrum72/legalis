<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CashFlowService
{
    public function report(array $period): array
    {
        $from = CarbonImmutable::parse($period['from'])->startOfDay();
        $until = CarbonImmutable::parse($period['to'])->addDay()->startOfDay();
        $entries = collect();
        $receipts = Payment::query()->with('invoice.client')->whereNull('cancelled_at')
            ->where('paid_at', '>=', $from)->where('paid_at', '<', $until)->get();
        foreach ($receipts as $payment) {
            $invoice = $payment->invoice;
            $entries->push($this->entry('receipt-'.$payment->id, 'realized', 'in', $payment->paid_at->toDateString(),
                $payment->amount_cents, $invoice?->client?->name ?? 'Cliente indisponível',
                $this->invoiceLabel($invoice), $payment->reference, $payment->method));
        }
        $disbursements = PayablePayment::query()->with('payable')->whereNull('cancelled_at')
            ->where('paid_at', '>=', $from)->where('paid_at', '<', $until)->get();
        foreach ($disbursements as $payment) {
            $entries->push($this->entry('disbursement-'.$payment->id, 'realized', 'out', $payment->paid_at->toDateString(),
                $payment->amount_cents, $payment->payable?->supplier ?? 'Fornecedor indisponível',
                $payment->payable?->description ?? 'Conta a pagar', $payment->reference, $payment->method));
        }

        // Projeção é o saldo pendente atual por vencimento, não um saldo histórico.
        $dueInvoices = Invoice::query()->with('client')->where('status', '!=', 'cancelled')
            ->whereBetween('due_on', [$period['from'], $period['to']])->get();
        foreach ($dueInvoices->whereIn('status', ['open', 'partial'])->where('balance_cents', '>', 0) as $invoice) {
            $entries->push($this->entry('invoice-'.$invoice->id, 'projected', 'in', $invoice->due_on->toDateString(),
                $invoice->balance_cents, $invoice->client?->name ?? 'Cliente indisponível', $this->invoiceLabel($invoice)));
        }
        $duePayables = Payable::query()->whereIn('status', ['open', 'partial'])->where('balance_cents', '>', 0)
            ->whereBetween('due_on', [$period['from'], $period['to']])->get();
        foreach ($duePayables as $payable) {
            $entries->push($this->entry('payable-'.$payable->id, 'projected', 'out', $payable->due_on->toDateString(),
                $payable->balance_cents, $payable->supplier, $payable->description));
        }
        $entries = $entries->sortBy([['date', 'asc'], ['id', 'asc']])->values();
        $realized = $this->totals($entries->where('stage', 'realized'));
        $projected = $this->totals($entries->where('stage', 'projected'));
        $byClient = $receipts->groupBy(fn ($payment) => $payment->invoice?->client_id ?? 'unknown')
            ->map(fn ($payments) => [
                'name' => $payments->first()->invoice?->client?->name ?? 'Cliente indisponível',
                'amount_cents' => (int) $payments->sum('amount_cents'),
            ])->sortByDesc('amount_cents')->first();
        $overdue = (int) $dueInvoices->whereIn('status', ['open', 'partial'])
            ->filter(fn ($invoice) => $invoice->due_on->toDateString() < today()->toDateString())->sum('balance_cents');
        $dueTotal = (int) $dueInvoices->where('status', '!=', 'draft')->sum('total_cents');
        $monthly = collect();
        for ($month = $from->startOfMonth(); $month->lt($until); $month = $month->addMonth()) {
            $rows = $entries->filter(fn ($entry) => str_starts_with($entry['date'], $month->format('Y-m')));
            $monthly->push([
                'month' => $month->format('Y-m'),
                'realized' => $this->totals($rows->where('stage', 'realized')),
                'projected' => $this->totals($rows->where('stage', 'projected')),
            ]);
        }

        return [
            'period' => $period,
            'as_of' => now()->toIso8601String(),
            'realized' => $realized,
            'projected' => $projected,
            'combined_net_cents' => $realized['net_cents'] + $projected['net_cents'],
            'indicators' => [
                'cash_margin_percent' => $this->percentage($realized['net_cents'], $realized['in_cents']),
                'overdue_percent' => $this->percentage($overdue, $dueTotal),
                'overdue_cents' => $overdue,
                'due_total_cents' => $dueTotal,
                'largest_client' => $byClient,
                'largest_client_percent' => $this->percentage($byClient['amount_cents'] ?? 0, $realized['in_cents']),
            ],
            'monthly' => $monthly->all(),
            'entries' => $entries->all(),
        ];
    }

    private function invoiceLabel(?Invoice $invoice): string
    {
        return $invoice
            ? 'Cobrança '.($invoice->charge_identifier ?: $invoice->number ?: '#'.$invoice->id).' · parcela '.$invoice->installment_number.'/'.$invoice->installment_count
            : 'Cobrança indisponível';
    }

    private function entry(string $id, string $stage, string $direction, string $date, int $amount, string $party, string $description, ?string $reference = null, ?string $method = null): array
    {
        return ['id' => $id, 'stage' => $stage, 'direction' => $direction, 'date' => $date,
            'amount_cents' => $amount, 'party' => $party, 'description' => $description,
            'reference' => $reference, 'method' => $method];
    }

    private function totals(Collection $entries): array
    {
        $in = (int) $entries->where('direction', 'in')->sum('amount_cents');
        $out = (int) $entries->where('direction', 'out')->sum('amount_cents');

        return ['in_cents' => $in, 'out_cents' => $out, 'net_cents' => $in - $out];
    }

    private function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator > 0 ? round($numerator / $denominator * 100, 2) : null;
    }
}
