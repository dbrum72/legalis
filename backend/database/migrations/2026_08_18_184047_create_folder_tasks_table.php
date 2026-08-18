<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'folder_tasks',
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
                    'title',
                    180,
                );

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->string(
                        'priority',
                        20,
                    )
                    ->default(
                        'medium'
                    );

                $table
                    ->dateTime(
                        'due_at'
                    )
                    ->nullable();

                $table
                    ->string(
                        'status',
                        20,
                    )
                    ->default(
                        'pending'
                    );

                $table
                    ->dateTime(
                        'completed_at'
                    )
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'folder_id',
                    'status',
                ]);

                $table->index([
                    'folder_id',
                    'priority',
                ]);

                $table->index([
                    'folder_id',
                    'due_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'folder_tasks'
        );
    }
};
