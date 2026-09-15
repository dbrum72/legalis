<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('path');
            $table->string('sha256', 64);
            $table->json('fields');
            $table->timestamps();
        });
        Schema::create('document_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_template_id')->constrained();
            $table->foreignId('folder_document_id')->constrained()->cascadeOnDelete();
            $table->json('values_snapshot');
            $table->string('template_sha256', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_generations');
        Schema::dropIfExists('document_templates');
    }
};
