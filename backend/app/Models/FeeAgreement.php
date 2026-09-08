<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'folder_id',
    'client_id',
    'type',
    'status',
    'hourly_rate_cents',
    'fixed_fee_cents',
    'contingency_percentage',
    'billing_day',
    'starts_on',
    'ends_on',
    'notes',
])]
class FeeAgreement extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'hourly_rate_cents' => 'integer',
            'fixed_fee_cents' => 'integer',
            'contingency_percentage' => 'decimal:2',
            'billing_day' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
