<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'organization_id',
    'monitored_bar_registration_id',
    'folder_id',
    'provider',
    'status',
    'period_start',
    'period_end',
    'started_at',
    'finished_at',
    'items_seen',
    'items_imported',
    'items_linked',
    'error_message',
    'metadata',
])]
class IntegrationSyncRun extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    public const STATUS_RUNNING =
        'running';

    public const STATUS_SUCCEEDED =
        'succeeded';

    public const STATUS_FAILED =
        'failed';

    protected function casts(): array
    {
        return [
            'period_start' => 'date',

            'period_end' => 'date',

            'started_at' => 'datetime',

            'finished_at' => 'datetime',

            'metadata' => 'array',
        ];
    }

    public function barRegistration(): BelongsTo
    {
        return $this->belongsTo(
            MonitoredBarRegistration::class,
            'monitored_bar_registration_id',
        );
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'integration_sync_run_views')
            ->withPivot('viewed_at')
            ->withTimestamps();
    }
}
