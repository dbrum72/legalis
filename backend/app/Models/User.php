<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable([
    'name',
    'email',
    'password',
])]
#[Hidden([
    'password',
])]

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name =
        'api';

    protected function getDefaultGuardName(): string
    {
        return $this->guard_name;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',

            'password' => 'hashed',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Organization::class
            )
            ->as('membership')
            ->withPivot([
                'status',
                'joined_at',
            ])
            ->withTimestamps();
    }

    public function sentOrganizationInvitations(): HasMany
    {
        return $this->hasMany(
            OrganizationInvitation::class,
            'invited_by'
        );
    }

    public function reviewedLegalPublications(): HasMany
    {
        return $this->hasMany(
            LegalPublication::class,
            'reviewed_by',
        );
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
