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
    'name',
    'process_number',
    'datajud_alias',
    'datajud_metadata',
    'datajud_synced_at',
    'datajud_monitoring_enabled',
    'datajud_last_attempt_at',
    'datajud_last_success_at',
    'datajud_next_sync_at',
    'datajud_sync_error',
])]
class Folder extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    protected function casts(): array
    {
        return [
            'datajud_metadata' => 'array',
            'datajud_synced_at' => 'datetime',
            'datajud_monitoring_enabled' => 'boolean',
            'datajud_last_attempt_at' => 'datetime',
            'datajud_last_success_at' => 'datetime',
            'datajud_next_sync_at' => 'datetime',
        ];
    }

    public function folderClients(): HasMany
    {
        return $this->hasMany(
            FolderClient::class
        );
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            FolderDocument::class
        );
    }

    public function movements(): HasMany
    {
        return $this->hasMany(
            FolderMovement::class
        );
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(
            FolderDeadline::class
        );
    }

    public function clients(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Client::class,
                'folder_clients',
            )
            ->withPivot([
                'id',
                'qualification_id',
            ])
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            FolderEvent::class
        );
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(
            FolderTask::class
        );
    }

    public function legalPublications(): HasMany
    {
        return $this->hasMany(
            LegalPublication::class
        );
    }
}
