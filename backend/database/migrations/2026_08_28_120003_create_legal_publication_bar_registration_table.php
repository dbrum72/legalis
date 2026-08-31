<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'legal_publication_bar_registration',
            function (Blueprint $table): void {
                $table
                    ->foreignId('legal_publication_id')
                    ->constrained('legal_publications')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('monitored_bar_registration_id')
                    ->constrained(
                        'monitored_bar_registrations',
                        indexName: 'legal_pub_bar_registration_fk',
                    )
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->primary([
                    'legal_publication_id',
                    'monitored_bar_registration_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'legal_publication_bar_registration'
        );
    }
};
