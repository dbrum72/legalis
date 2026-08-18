<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'folder_events',
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
                    'type',
                    30,
                );

                $table->string(
                    'title',
                    180,
                );

                $table
                    ->text('description')
                    ->nullable();

                $table->dateTime(
                    'starts_at'
                );

                $table
                    ->dateTime(
                        'ends_at'
                    )
                    ->nullable();

                $table
                    ->string(
                        'location',
                        255,
                    )
                    ->nullable();

                $table
                    ->string(
                        'status',
                        20,
                    )
                    ->default(
                        'scheduled'
                    );

                $table
                    ->dateTime(
                        'completed_at'
                    )
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'folder_id',
                    'starts_at',
                ]);

                $table->index([
                    'folder_id',
                    'status',
                ]);

                $table->index([
                    'folder_id',
                    'type',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'folder_events'
        );
    }
};
