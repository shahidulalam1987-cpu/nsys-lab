<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_id')->unique();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('department');
            $table->string('role');
            $table->date('joining_date');
            $table->date('confirmation_date')->nullable();
            $table->date('last_working_date')->nullable();
            $table->string('status')->default('probation');
            $table->string('salary_type')->default('monthly');
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->text('mobile_banking_info')->nullable();
            $table->timestamps();

            $table->index(['status', 'joining_date']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
