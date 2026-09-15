<?php

namespace App\Models\Concerns;

use App\Models\FinancialClassification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasFinancialClassification
{
    public function financialCategory(): BelongsTo
    {
        return $this->belongsTo(FinancialClassification::class, 'category_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(FinancialClassification::class, 'cost_center_id');
    }
}
