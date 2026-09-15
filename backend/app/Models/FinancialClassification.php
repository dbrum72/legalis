<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['organization_id', 'kind', 'name', 'active'])]
class FinancialClassification extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
