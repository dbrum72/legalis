<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'process_number',
    ];

    public function folderClients(): HasMany
    {
        return $this->hasMany(FolderClient::class);
    }

    public function clients(): BelongsToMany
    {
        return $this
            ->belongsToMany(Client::class,'folder_clients')
            ->withPivot([
                'id',
                'qualification_id',
            ])
            ->withTimestamps();
    }
}
