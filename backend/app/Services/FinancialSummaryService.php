<?php

namespace App\Services;

use App\Models\Invoice;

class FinancialSummaryService
{
    public function get(): array
    {
        $receivable = Invoice::query()->whereIn('status', ['open', 'partial']);
        $overdue = (clone $receivable)->whereDate('due_on', '<', today());
        $monthRange = [now()->startOfMonth(), now()->endOfMonth()];
        $receivedThisMonth = Invoice::query()
            ->whereHas('payments', fn ($query) => $query->whereBetween('paid_at', $monthRange))
            ->withSum(['payments as month_paid_cents' => fn ($query) => $query->whereBetween('paid_at', $monthRange)], 'amount_cents')
            ->get()
            ->sum('month_paid_cents');

        return [
            'receivable_cents' => (int) (clone $receivable)->sum('balance_cents'),
            'overdue_cents' => (int) $overdue->sum('balance_cents'),
            'overdue_count' => (int) $overdue->count(),
            'received_this_month_cents' => (int) $receivedThisMonth,
        ];
    }
}
