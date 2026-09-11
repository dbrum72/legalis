<?php

namespace App\Services;

use App\Models\Invoice;

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

        return [
            'receivable_cents' => (int) (clone $receivable)->sum('balance_cents'),
            'overdue_cents' => (int) $overdue->sum('balance_cents'),
            'overdue_count' => (int) $overdue->count(),
            'received_this_month_cents' => (int) $receivedThisMonth,
            'aging' => $aging,
        ];
    }

    private function agingBucket($query): array
    {
        return [
            'count' => (int) (clone $query)->count(),
            'balance_cents' => (int) $query->sum('balance_cents'),
        ];
    }
}
