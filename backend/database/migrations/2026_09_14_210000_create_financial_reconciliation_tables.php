<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payable_payment_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('observed_amount_cents');
            $table->date('observed_on');
            $table->string('reference', 180);
            $table->text('note');
            $table->string('source_hash', 64);
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique('payment_id');
            $table->unique('payable_payment_id');
        });
        Schema::create('financial_closings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('month', 7);
            $table->json('snapshot');
            $table->string('fingerprint', 64);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('note');
            $table->timestamps();
            $table->unique(['organization_id', 'month']);
        });
        Schema::create('financial_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('month', 7);
            $table->string('action', 50);
            $table->string('source_key', 80);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->text('note')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at');
            $table->index(['organization_id', 'month', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_audit_events');
        Schema::dropIfExists('financial_closings');
        Schema::dropIfExists('financial_reconciliations');
    }
};
