<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->date('salary_month');
            $table->decimal('payable_salary', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('status')->default('unpaid');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['salary_month', 'status']);
            $table->index(['employee_id', 'salary_month']);
            $table->index(['client_id', 'salary_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payrolls');
    }
};
