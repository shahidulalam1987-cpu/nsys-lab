<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_account_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->foreignId('employee_payroll_id')->nullable()->constrained('employee_payrolls')->nullOnDelete();
            $table->date('ledger_date');
            $table->string('transaction_type');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('previous_balance', 16, 2)->nullable();
            $table->decimal('new_balance', 16, 2)->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ledger_date', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_ledgers');
    }
};
