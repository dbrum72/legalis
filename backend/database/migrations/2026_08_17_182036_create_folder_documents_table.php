<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'folder_documents',
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

                $table->string(
                    'name',
                    150,
                );

                $table->string(
                    'original_name',
                    255,
                );

                $table->string(
                    'path',
                    500,
                );

                $table->string(
                    'mime_type',
                    150,
                );

                $table->unsignedBigInteger(
                    'size',
                );

                $table
                    ->text('description')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'folder_id',
                    'created_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'folder_documents',
        );
    }
};
