<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->string('person_name');
            $table->string('relation')->nullable();
            $table->string('expense_category');
            $table->decimal('amount', 16, 2);
            $table->string('payment_method')->nullable();
            $table->foreignId('finance_account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_expenses');
    }
};
