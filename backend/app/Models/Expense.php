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
    'incurred_on',
    'description',
    'amount_cents',
    'reimbursable',
    'receipt_path',
    'status',
])]
class Expense extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'incurred_on' => 'date',
            'amount_cents' => 'integer',
            'reimbursable' => 'boolean',
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
}
