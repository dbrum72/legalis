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
    'folder_id',
    'source',
    'external_id',
    'source_hash',
    'process_number',
    'normalized_process_number',
    'court_acronym',
    'judicial_body',
    'communication_type',
    'document_type',
    'medium',
    'available_on',
    'published_on',
    'content',
    'recipients',
    'lawyers',
    'raw_payload',
    'payload_hash',
    'review_status',
    'reviewed_by',
    'reviewed_at',
    'imported_at',
    'last_seen_at',
])]
class LegalPublication extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    public const REVIEW_PENDING =
        'pending_review';

    public const REVIEWED =
        'reviewed';

    public const IGNORED =
        'ignored';

    protected function casts(): array
    {
        return [
            'available_on' => 'date',

            'published_on' => 'date',

            'recipients' => 'array',

            'lawyers' => 'array',

            'raw_payload' => 'array',

            'reviewed_at' => 'datetime',

            'imported_at' => 'datetime',

            'last_seen_at' => 'datetime',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(
            Folder::class
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by',
        );
    }

    public function barRegistrations(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                MonitoredBarRegistration::class,
                'legal_publication_bar_registration',
            )
            ->withTimestamps();
    }
}
