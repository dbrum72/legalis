<?php

namespace App\Observers;

use App\Services\FinancialAudit;
use Illuminate\Database\Eloquent\Model;

class FinancialPaymentObserver
{
    public function created(Model $payment): void
    {
        $source = FinancialAudit::source($payment);
        FinancialAudit::record($payment->organization_id, substr($source['paid_at'], 0, 7), 'payment.created', $source['key'], null, $source);
    }

    public function updated(Model $payment): void
    {
        if (! $payment->wasChanged(['paid_at', 'amount_cents', 'method', 'reference', 'cancelled_at'])) {
            return;
        }
        $previous = $payment->newInstance([], true);
        $previous->setRawAttributes($payment->getRawOriginal());
        $before = FinancialAudit::source($previous);
        $after = FinancialAudit::source($payment);
        foreach (array_unique([substr($before['paid_at'], 0, 7), substr($after['paid_at'], 0, 7)]) as $month) {
            FinancialAudit::record($payment->organization_id, $month, $payment->wasChanged('cancelled_at') ? 'payment.cancelled' : 'payment.updated', $after['key'], $before, $after, (string) $payment->cancellation_reason);
        }
    }
}
