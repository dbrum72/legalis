<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payable_recurrences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->json('template');
            $table->date('starts_on');
            $table->unsignedTinyInteger('interval_months');
            $table->date('ends_on')->nullable();
            $table->date('next_due_on')->nullable();
            $table->boolean('active')->default(true);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'active', 'next_due_on']);
        });
        Schema::create('payable_recurrence_occurrences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payable_recurrence_id')->constrained()->restrictOnDelete();
            $table->foreignId('payable_id')->constrained()->restrictOnDelete();
            $table->date('due_on');
            $table->unique(['payable_recurrence_id', 'due_on'], 'payable_recurrence_due_unique');
            $table->unique('payable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payable_recurrence_occurrences');
        Schema::dropIfExists('payable_recurrences');
    }
};
