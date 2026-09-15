<?php

namespace App\Services;

use App\Models\PayablePayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialAudit
{
    public static function source(Model $payment): array
    {
        return [
            'key' => ($payment instanceof PayablePayment ? 'outgoing:' : 'incoming:').$payment->id,
            'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
            'amount_cents' => (int) $payment->amount_cents,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'cancelled_at' => $payment->cancelled_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function hash(array $data): string
    {
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    public static function record(int $organizationId, string $month, string $action, string $key, ?array $before, ?array $after, ?string $note = null): void
    {
        $actor = auth('api')->user();
        DB::table('financial_audit_events')->insert([
            'organization_id' => $organizationId, 'month' => $month, 'action' => $action,
            'source_key' => $key, 'actor_id' => $actor?->id, 'actor_name' => $actor?->name,
            'note' => $note, 'before' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
