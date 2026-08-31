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
])]
class Folder extends Model
{
    use BelongsToOrganization;
    use HasFactory;

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
