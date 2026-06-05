<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('salary_month');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->string('transaction_id');
            $table->string('screenshot')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'salary_month']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
