<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'invoice_id',
    'recorded_by',
    'cancelled_by',
    'paid_at',
    'amount_cents',
    'method',
    'reference',
    'notes',
    'cancellation_reason',
    'cancelled_at',
])]
class Payment extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount_cents' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function receiptDeliveries(): HasMany
    {
        return $this->hasMany(PaymentReceiptDelivery::class);
    }
}
