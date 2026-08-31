<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_sync_run_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_sync_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->unique(['integration_sync_run_id', 'user_id'], 'sync_run_user_view_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_sync_run_views');
    }
};
