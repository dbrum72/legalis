<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'lawyer_name',
    'bar_number',
    'state',
    'active',
    'monitoring_started_on',
    'last_synced_at',
])]
class MonitoredBarRegistration extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',

            'monitoring_started_on' => 'date',

            'last_synced_at' => 'datetime',
        ];
    }

    public function publications(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                LegalPublication::class,
                'legal_publication_bar_registration',
            )
            ->withTimestamps();
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(
            IntegrationSyncRun::class
        );
    }
}
