<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_type');
            $table->string('account_name');
            $table->string('provider_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->decimal('current_balance', 16, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_type');
            $table->string('person_company_name');
            $table->decimal('amount', 16, 2);
            $table->date('loan_date');
            $table->date('due_date')->nullable();
            $table->decimal('paid_amount', 16, 2)->default(0);
            $table->decimal('remaining_balance', 16, 2)->default(0);
            $table->string('status')->default('open');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_loan_id')->constrained('finance_loans')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 16, 2);
            $table->string('method')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_loan_repayments');
        Schema::dropIfExists('finance_loans');
        Schema::dropIfExists('finance_accounts');
    }
};
