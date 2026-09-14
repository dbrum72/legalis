<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['organization_id', 'name', 'days_after_due', 'subject', 'message', 'active'])]
class InvoiceReminderRule extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return ['days_after_due' => 'integer', 'active' => 'boolean'];
    }
}
