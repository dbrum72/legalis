<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasFinancialClassification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'supplier', 'description', 'category', 'category_id', 'cost_center_id', 'folder_id', 'client_id', 'due_on', 'amount_cents', 'paid_cents', 'balance_cents', 'status', 'notes', 'cancellation_reason', 'cancelled_at'])]
class Payable extends Model
{
    use BelongsToOrganization;
    use HasFinancialClassification;

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    protected function casts(): array
    {
        return ['due_on' => 'date', 'amount_cents' => 'integer', 'paid_cents' => 'integer', 'balance_cents' => 'integer', 'cancelled_at' => 'datetime'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayablePayment::class);
    }
}
