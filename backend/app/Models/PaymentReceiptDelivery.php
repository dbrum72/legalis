<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'payment_id', 'sent_by', 'recipient', 'sent_at'])]
class PaymentReceiptDelivery extends Model
{
    use BelongsToOrganization;

    protected function casts(): array { return ['sent_at' => 'datetime']; }
    public function sentBy(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }
}
