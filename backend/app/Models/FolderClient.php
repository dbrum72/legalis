<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderClient extends Model
{
    protected $fillable = [
        'folder_id',
        'client_id',
        'qualification_id',
    ];

    protected static function booted(): void
    {
        static::saving(
            function (FolderClient $folderClient): void {
                $folderClient
                    ->ensureSameOrganization();
            }
        );
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(
            Folder::class
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            Client::class
        );
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(
            Qualification::class
        );
    }

    private function ensureSameOrganization(): void
    {
        if (
            ! $this->folder_id ||
            ! $this->client_id
        ) {
            return;
        }

        $folderOrganizationId =
            Folder::withoutGlobalScopes()
            ->whereKey(
                $this->folder_id
            )
            ->value(
                'organization_id'
            );

        $clientOrganizationId =
            Client::withoutGlobalScopes()
            ->whereKey(
                $this->client_id
            )
            ->value(
                'organization_id'
            );

        if (
            $folderOrganizationId === null ||
            $clientOrganizationId === null
        ) {
            return;
        }

        if (
            (int) $folderOrganizationId !==
            (int) $clientOrganizationId
        ) {
            throw new DomainException(
                'A pasta e o cliente devem pertencer à mesma organização.'
            );
        }
    }
}
