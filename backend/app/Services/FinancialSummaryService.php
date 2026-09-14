<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payable;
use App\Models\PayablePayment;

class FinancialSummaryService
{
    public function get(): array
    {
        $receivable = Invoice::query()->whereIn('status', ['open', 'partial']);
        $overdue = (clone $receivable)->whereDate('due_on', '<', today());
        $aging = [
            'current' => $this->agingBucket((clone $receivable)->whereDate('due_on', '>=', today())),
            'days_1_30' => $this->agingBucket((clone $receivable)->whereBetween('due_on', [today()->subDays(30), today()->subDay()])),
            'days_31_60' => $this->agingBucket((clone $receivable)->whereBetween('due_on', [today()->subDays(60), today()->subDays(31)])),
            'days_61_90' => $this->agingBucket((clone $receivable)->whereBetween('due_on', [today()->subDays(90), today()->subDays(61)])),
            'over_90' => $this->agingBucket((clone $receivable)->whereDate('due_on', '<=', today()->subDays(91))),
        ];
        $monthRange = [now()->startOfMonth(), now()->endOfMonth()];
        $receivedThisMonth = Invoice::query()
            ->whereHas('payments', fn ($query) => $query->whereNull('cancelled_at')->whereBetween('paid_at', $monthRange))
            ->withSum(['payments as month_paid_cents' => fn ($query) => $query->whereNull('cancelled_at')->whereBetween('paid_at', $monthRange)], 'amount_cents')
            ->get()
            ->sum('month_paid_cents');
        $forecast = [
            'overdue' => $this->forecastBucket((clone $receivable)->whereDate('due_on', '<', today())),
            'next_30_days' => $this->forecastBucket((clone $receivable)->whereBetween('due_on', [today(), today()->addDays(30)])),
            'days_31_60' => $this->forecastBucket((clone $receivable)->whereBetween('due_on', [today()->addDays(31), today()->addDays(60)])),
            'days_61_90' => $this->forecastBucket((clone $receivable)->whereBetween('due_on', [today()->addDays(61), today()->addDays(90)])),
            'after_90_days' => $this->forecastBucket((clone $receivable)->whereDate('due_on', '>', today()->addDays(90))),
        ];
        $monthlyPerformance = collect(range(5, 0))->map(function (int $monthsAgo): array {
            $month = now()->startOfMonth()->subMonths($monthsAgo);
            $range = [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];

            return [
                'month' => $month->format('Y-m'),
                'billed_cents' => (int) Invoice::query()->where('status', '!=', 'cancelled')->whereBetween('issued_on', $range)->sum('total_cents'),
                'received_cents' => (int) Payment::query()->whereNull('cancelled_at')->whereBetween('paid_at', $range)->sum('amount_cents'),
                'paid_expenses_cents' => (int) PayablePayment::query()->whereNull('cancelled_at')->whereBetween('paid_at', $range)->sum('amount_cents'),
            ];
        })->values()->all();
        $overdueClients = Invoice::query()
            ->with('client:id,name,email')
            ->withMax(['reminders as last_reminder_at' => fn ($query) => $query->where('status', 'sent')], 'sent_at')
            ->whereIn('status', ['open', 'partial'])
            ->where('balance_cents', '>', 0)
            ->whereDate('due_on', '<', today())
            ->get()
            ->groupBy('client_id')
            ->map(function ($invoices): array {
                $client = $invoices->first()->client;

                return [
                    'client_id' => (int) $client->id,
                    'client_name' => $client->name,
                    'client_email' => $client->email,
                    'invoice_count' => $invoices->count(),
                    'balance_cents' => (int) $invoices->sum('balance_cents'),
                    'oldest_due_on' => $invoices->min(fn ($invoice) => $invoice->due_on?->format('Y-m-d')),
                    'last_reminder_at' => $invoices->max('last_reminder_at'),
                ];
            })
            ->sortByDesc('balance_cents')
            ->values()
            ->all();
        $payables = Payable::query()->whereIn('status', ['open', 'partial']);
        $payablesPaidThisMonth = PayablePayment::query()->whereNull('cancelled_at')->whereBetween('paid_at', $monthRange)->sum('amount_cents');
        $performanceTotals = [
            'billed_cents' => collect($monthlyPerformance)->sum('billed_cents'),
            'received_cents' => collect($monthlyPerformance)->sum('received_cents'),
            'paid_expenses_cents' => collect($monthlyPerformance)->sum('paid_expenses_cents'),
        ];
        $performanceTotals['cash_result_cents'] = $performanceTotals['received_cents'] - $performanceTotals['paid_expenses_cents'];

        return [
            'receivable_cents' => (int) (clone $receivable)->sum('balance_cents'),
            'overdue_cents' => (int) $overdue->sum('balance_cents'),
            'overdue_count' => (int) $overdue->count(),
            'received_this_month_cents' => (int) $receivedThisMonth,
            'aging' => $aging,
            'forecast' => $forecast,
            'forecast_90_days_cents' => collect($forecast)->only(['next_30_days', 'days_31_60', 'days_61_90'])->sum('balance_cents'),
            'monthly_performance' => $monthlyPerformance,
            'performance_totals' => $performanceTotals,
            'overdue_clients' => $overdueClients,
            'payable_cents' => (int) (clone $payables)->sum('balance_cents'),
            'payable_overdue_cents' => (int) (clone $payables)->whereDate('due_on', '<', today())->sum('balance_cents'),
            'payable_overdue_count' => (int) (clone $payables)->whereDate('due_on', '<', today())->count(),
            'paid_expenses_this_month_cents' => (int) $payablesPaidThisMonth,
            'net_position_cents' => (int) (clone $receivable)->sum('balance_cents') - (int) (clone $payables)->sum('balance_cents'),
        ];
    }

    private function agingBucket($query): array
    {
        return [
            'count' => (int) (clone $query)->count(),
            'balance_cents' => (int) $query->sum('balance_cents'),
        ];
    }

    private function forecastBucket($query): array
    {
        return [
            'count' => (int) (clone $query)->count(),
            'balance_cents' => (int) $query->sum('balance_cents'),
        ];
    }
}
