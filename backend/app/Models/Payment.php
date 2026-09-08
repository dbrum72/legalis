<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'invoice_id',
    'recorded_by',
    'paid_at',
    'amount_cents',
    'method',
    'reference',
    'notes',
])]
class Payment extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount_cents' => 'integer',
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
}
