<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class FinancialSummaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $receivable = Invoice::query()->whereIn('status', ['open', 'partial']);
        $overdue = (clone $receivable)->whereDate('due_on', '<', today());
        $receivedThisMonth = Invoice::query()
            ->whereHas('payments', fn ($query) => $query->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()]))
            ->withSum(['payments as month_paid_cents' => fn ($query) => $query->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])], 'amount_cents')
            ->get()
            ->sum('month_paid_cents');

        return response()->json([
            'receivable_cents' => (int) (clone $receivable)->sum('balance_cents'),
            'overdue_cents' => (int) $overdue->sum('balance_cents'),
            'overdue_count' => (int) $overdue->count(),
            'received_this_month_cents' => (int) $receivedThisMonth,
        ]);
    }
}
