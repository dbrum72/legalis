<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'document',
    'identity_document',
    'identity_issuer',
    'marital_status_id',
    'profession',
    'address',
    'address_complement',
    'district',
    'city',
    'postal_code',
    'phone',
    'whatsapp',
    'email',
])]

class Client extends Model
{
    protected function casts(): array
    {
        return [
            'whatsapp' => 'boolean',
        ];
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }
}