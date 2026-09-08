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
    'fee_agreement_id',
    'number',
    'charge_identifier',
    'installment_number',
    'installment_count',
    'issued_on',
    'due_on',
    'status',
    'subtotal_cents',
    'discount_cents',
    'total_cents',
    'paid_cents',
    'balance_cents',
    'notes',
])]
class Invoice extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'due_on' => 'date',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
            'paid_cents' => 'integer',
            'balance_cents' => 'integer',
            'installment_number' => 'integer',
            'installment_count' => 'integer',
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

    public function feeAgreement(): BelongsTo
    {
        return $this->belongsTo(FeeAgreement::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
