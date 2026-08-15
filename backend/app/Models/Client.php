<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'name',
    'document',
    'identity_document',
    'identity_issuer',
    'marital_status_id',
    'profession',
    'address',
    'address_complement',
    'district',
    'city',
    'postal_code',
    'phone',
    'whatsapp',
    'email',
])]
class Client extends Model
{
    use HasFactory;
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'whatsapp' => 'boolean',
        ];
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(
            MaritalStatus::class
        );
    }

    public function folderClients(): HasMany
    {
        return $this->hasMany(
            FolderClient::class
        );
    }

    public function folders(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Folder::class,
                'folder_clients',
            )
            ->withPivot([
                'id',
                'qualification_id',
            ])
            ->withTimestamps();
    }
}
