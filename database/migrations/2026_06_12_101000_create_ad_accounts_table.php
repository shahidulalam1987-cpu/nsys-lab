<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('ad_account_name');
            $table->string('ad_account_id')->unique();
            $table->foreignId('business_manager_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency')->default('BDT');
            $table->string('timezone')->default('Asia/Dhaka');
            $table->decimal('threshold_amount', 14, 2)->default(0);
            $table->decimal('current_threshold_usage', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->unsignedTinyInteger('monthly_billing_date')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
