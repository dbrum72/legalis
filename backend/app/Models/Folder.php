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
    use HasFactory;
    use BelongsToOrganization;

    public function folderClients(): HasMany
    {
        return $this->hasMany(
            FolderClient::class
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
}