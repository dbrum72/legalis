<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'folder_movements',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('folder_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->dateTime(
                    'occurred_at'
                );

                $table->string(
                    'title',
                    180,
                );

                $table
                    ->text('description')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'folder_id',
                    'occurred_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'folder_movements'
        );
    }
};
