<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table): void {
            $table->string('datajud_alias', 40)->nullable()->after('process_number');
            $table->json('datajud_metadata')->nullable()->after('datajud_alias');
            $table->timestamp('datajud_synced_at')->nullable()->after('datajud_metadata');
        });

        Schema::table('folder_movements', function (Blueprint $table): void {
            $table->string('source', 30)->nullable()->after('user_id');
            $table->string('external_id', 64)->nullable()->after('source');
            $table->unique(
                ['folder_id', 'source', 'external_id'],
                'folder_movements_source_external_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('folder_movements', function (Blueprint $table): void {
            $table->dropUnique('folder_movements_source_external_unique');
            $table->dropColumn(['source', 'external_id']);
        });

        Schema::table('folders', function (Blueprint $table): void {
            $table->dropColumn(['datajud_alias', 'datajud_metadata', 'datajud_synced_at']);
        });
    }
};
