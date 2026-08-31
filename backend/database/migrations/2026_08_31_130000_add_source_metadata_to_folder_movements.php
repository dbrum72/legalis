<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folder_movements', function (Blueprint $table): void {
            $table->string('source_code', 30)->nullable()->after('external_id');
            $table->json('source_metadata')->nullable()->after('source_code');
        });
    }

    public function down(): void
    {
        Schema::table('folder_movements', function (Blueprint $table): void {
            $table->dropColumn(['source_code', 'source_metadata']);
        });
    }
};
