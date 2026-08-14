<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->string('document', 14);

            $table
                ->string('identity_document', 20)
                ->nullable();

            $table
                ->string('identity_issuer', 30)
                ->nullable();

            $table
                ->foreignId('marital_status_id')
                ->nullable()
                ->constrained('marital_statuses')
                ->nullOnDelete();

            $table
                ->string('profession', 100)
                ->nullable();

            $table
                ->string('address', 150)
                ->nullable();

            $table
                ->string('address_complement', 100)
                ->nullable();

            $table
                ->string('district', 100)
                ->nullable();

            $table
                ->string('city', 100)
                ->nullable();

            $table
                ->string('postal_code', 8)
                ->nullable();

            $table
                ->string('phone', 11)
                ->nullable();

            $table
                ->boolean('whatsapp')
                ->default(false);

            $table
                ->string('email')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'organization_id',
                'document',
            ]);

            $table->index([
                'organization_id',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};