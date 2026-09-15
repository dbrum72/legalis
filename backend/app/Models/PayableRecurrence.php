<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class PayableRecurrence extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'template', 'starts_on', 'interval_months', 'ends_on', 'next_due_on', 'active', 'last_error'];

    protected function casts(): array
    {
        return ['template' => 'array', 'starts_on' => 'immutable_date:Y-m-d', 'ends_on' => 'immutable_date:Y-m-d', 'next_due_on' => 'immutable_date:Y-m-d', 'active' => 'boolean', 'interval_months' => 'integer'];
    }
}
