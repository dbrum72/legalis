<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'legal_publications',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table
                    ->foreignId('folder_id')
                    ->nullable()
                    ->constrained('folders')
                    ->nullOnDelete();

                $table
                    ->string('source', 20)
                    ->default('djen');

                $table->string(
                    'external_id',
                    128,
                );

                $table
                    ->string('source_hash', 128)
                    ->nullable();

                $table
                    ->string('process_number', 25)
                    ->nullable();

                $table
                    ->char('normalized_process_number', 20)
                    ->nullable();

                $table
                    ->string('court_acronym', 20)
                    ->nullable();

                $table
                    ->string('judicial_body')
                    ->nullable();

                $table
                    ->string('communication_type', 100)
                    ->nullable();

                $table
                    ->string('document_type', 100)
                    ->nullable();

                $table
                    ->string('medium', 30)
                    ->nullable();

                $table
                    ->date('available_on')
                    ->nullable();

                $table
                    ->date('published_on')
                    ->nullable();

                $table->longText('content');

                $table
                    ->json('recipients')
                    ->nullable();

                $table
                    ->json('lawyers')
                    ->nullable();

                $table->json('raw_payload');

                $table->char(
                    'payload_hash',
                    64,
                );

                $table
                    ->string('review_status', 30)
                    ->default('pending_review');

                $table
                    ->foreignId('reviewed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->dateTime('reviewed_at')
                    ->nullable();

                $table->dateTime('imported_at');
                $table->dateTime('last_seen_at');

                $table->timestamps();

                $table->unique([
                    'organization_id',
                    'source',
                    'external_id',
                ]);

                $table->index([
                    'organization_id',
                    'folder_id',
                    'review_status',
                ]);

                $table->index([
                    'organization_id',
                    'normalized_process_number',
                ], 'legal_pub_org_process_number_index');

                $table->index([
                    'organization_id',
                    'available_on',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'legal_publications'
        );
    }
};
