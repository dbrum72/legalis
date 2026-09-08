<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_agreements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('hourly_rate_cents')->nullable();
            $table->unsignedBigInteger('fixed_fee_cents')->nullable();
            $table->decimal('contingency_percentage', 5, 2)->nullable();
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'folder_id']);
            $table->index(['organization_id', 'client_id']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 40);
            $table->string('charge_identifier', 40);
            $table->unsignedSmallInteger('installment_number')->default(1);
            $table->unsignedSmallInteger('installment_count')->default(1);
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedBigInteger('paid_cents')->default(0);
            $table->unsignedBigInteger('balance_cents')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'number']);
            $table->index(['organization_id', 'status', 'due_on']);
            $table->index(['organization_id', 'client_id']);
            $table->index(['organization_id', 'folder_id']);
            $table->index(['organization_id', 'charge_identifier']);
        });

        Schema::create('time_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->date('worked_on');
            $table->unsignedInteger('duration_minutes');
            $table->string('description', 500);
            $table->unsignedBigInteger('hourly_rate_cents')->default(0);
            $table->boolean('billable')->default(true);
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['organization_id', 'worked_on']);
            $table->index(['organization_id', 'folder_id', 'status']);
            $table->index(['organization_id', 'user_id', 'worked_on']);
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->date('incurred_on');
            $table->string('description', 500);
            $table->unsignedBigInteger('amount_cents');
            $table->boolean('reimbursable')->default(true);
            $table->string('receipt_path')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['organization_id', 'incurred_on']);
            $table->index(['organization_id', 'folder_id', 'status']);
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at');
            $table->unsignedBigInteger('amount_cents');
            $table->string('method', 30);
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'paid_at']);
            $table->index(['organization_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fee_agreements');
    }
};
