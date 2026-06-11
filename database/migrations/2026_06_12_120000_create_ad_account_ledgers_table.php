<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_account_ledgers', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('previous_value', 14, 2)->nullable();
            $table->decimal('new_value', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ad_account_id', 'transaction_date']);
            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_account_ledgers');
    }
};
