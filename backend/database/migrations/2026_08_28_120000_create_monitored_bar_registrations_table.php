<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'monitored_bar_registrations',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('organization_id')
                    ->constrained('organizations')
                    ->restrictOnDelete();

                $table->string(
                    'lawyer_name',
                    180,
                );

                $table->string(
                    'bar_number',
                    20,
                );

                $table->char(
                    'state',
                    2,
                );

                $table
                    ->boolean('active')
                    ->default(true);

                $table
                    ->date('monitoring_started_on')
                    ->nullable();

                $table
                    ->dateTime('last_synced_at')
                    ->nullable();

                $table->timestamps();

                $table->unique([
                    'organization_id',
                    'state',
                    'bar_number',
                ], 'monitored_bar_org_state_number_unique');

                $table->index([
                    'organization_id',
                    'active',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'monitored_bar_registrations'
        );
    }
};
