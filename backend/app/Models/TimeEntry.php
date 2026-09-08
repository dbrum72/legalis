<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'folder_id',
    'user_id',
    'invoice_id',
    'worked_on',
    'duration_minutes',
    'description',
    'hourly_rate_cents',
    'billable',
    'status',
])]
class TimeEntry extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'duration_minutes' => 'integer',
            'hourly_rate_cents' => 'integer',
            'billable' => 'boolean',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function billableAmountCents(): int
    {
        if (! $this->billable) {
            return 0;
        }

        return (int) round(
            ($this->duration_minutes * $this->hourly_rate_cents) / 60
        );
    }
}
