<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'integration_sync_runs',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table
                    ->foreignId('monitored_bar_registration_id')
                    ->nullable()
                    ->constrained('monitored_bar_registrations')
                    ->nullOnDelete();

                $table->string(
                    'provider',
                    30,
                );

                $table->string(
                    'status',
                    20,
                );

                $table->date('period_start');
                $table->date('period_end');
                $table->dateTime('started_at');

                $table
                    ->dateTime('finished_at')
                    ->nullable();

                $table
                    ->unsignedInteger('items_seen')
                    ->default(0);

                $table
                    ->unsignedInteger('items_imported')
                    ->default(0);

                $table
                    ->unsignedInteger('items_linked')
                    ->default(0);

                $table
                    ->text('error_message')
                    ->nullable();

                $table
                    ->json('metadata')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'organization_id',
                    'provider',
                    'started_at',
                ]);

                $table->index([
                    'monitored_bar_registration_id',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'integration_sync_runs'
        );
    }
};
