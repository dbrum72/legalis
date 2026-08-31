<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->string('name', 100);

            $table
                ->string('process_number', 25)
                ->nullable();

            $table->string('datajud_alias', 40)->nullable();
            $table->json('datajud_metadata')->nullable();
            $table->timestamp('datajud_synced_at')->nullable();
            $table->boolean('datajud_monitoring_enabled')->default(false);
            $table->timestamp('datajud_last_attempt_at')->nullable();
            $table->timestamp('datajud_last_success_at')->nullable();
            $table->timestamp('datajud_next_sync_at')->nullable();
            $table->text('datajud_sync_error')->nullable();

            $table->timestamps();

            $table->index([
                'organization_id',
                'name',
            ]);

            $table->index([
                'organization_id',
                'process_number',
            ]);

            $table->index(
                ['datajud_monitoring_enabled', 'datajud_next_sync_at'],
                'folders_datajud_monitoring_due_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
