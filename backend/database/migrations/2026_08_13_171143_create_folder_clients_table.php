<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folder_clients', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('folder_id')
                ->constrained('folders')
                ->cascadeOnDelete();

            $table
                ->foreignId('client_id')
                ->constrained('clients')
                ->cascadeOnDelete();

            $table
                ->foreignId('qualification_id')
                ->constrained('qualifications')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'folder_id',
                'client_id',
                'qualification_id',
            ]);

            $table->index([
                'folder_id',
                'client_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_clients');
    }
};
