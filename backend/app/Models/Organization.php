<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'status',
])]
class Organization extends Model
{
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class)
            ->as('membership')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }
}
