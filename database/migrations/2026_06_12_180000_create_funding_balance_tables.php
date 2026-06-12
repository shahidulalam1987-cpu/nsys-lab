<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funding_balances', function (Blueprint $table) {
            $table->id();
            $table->string('source')->unique();
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('balance_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('funding_balance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_balance_id')->nullable()->constrained('funding_balances')->nullOnDelete();
            $table->string('source');
            $table->decimal('previous_balance', 14, 2)->default(0);
            $table->decimal('new_balance', 14, 2)->default(0);
            $table->decimal('difference', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('balance_date');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funding_balance_history');
        Schema::dropIfExists('funding_balances');
    }
};
