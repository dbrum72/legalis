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

            $table->string('name', 100);

            $table
                ->string('process_number', 25)
                ->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('process_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
