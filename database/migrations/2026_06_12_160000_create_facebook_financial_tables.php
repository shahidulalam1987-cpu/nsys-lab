<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('binance_purchases', function (Blueprint $table) {
            $table->id();
            $table->date('purchase_date');
            $table->decimal('usd_amount', 14, 2);
            $table->decimal('buy_rate', 14, 4);
            $table->decimal('total_bdt_cost', 16, 2);
            $table->string('source')->nullable();
            $table->string('seller_name')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('card_loads', function (Blueprint $table) {
            $table->id();
            $table->date('load_date');
            $table->foreignId('facebook_card_id')->constrained('facebook_cards')->cascadeOnDelete();
            $table->foreignId('binance_purchase_id')->constrained()->restrictOnDelete();
            $table->decimal('usd_loaded', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('card_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->foreignId('facebook_card_id')->constrained('facebook_cards')->restrictOnDelete();
            $table->foreignId('binance_purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('spend_usd', 14, 2)->default(0);
            $table->decimal('fee_usd', 14, 2)->default(0);
            $table->decimal('total_deducted_usd', 14, 2)->default(0);
            $table->decimal('buy_rate', 14, 4)->default(0);
            $table->decimal('bdt_cost', 16, 2)->default(0);
            $table->decimal('client_rate', 14, 4)->default(0);
            $table->decimal('client_revenue', 16, 2)->default(0);
            $table->decimal('net_profit', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('profit_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_month');
            $table->string('report_type');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('spend_usd', 14, 2)->default(0);
            $table->decimal('fee_usd', 14, 2)->default(0);
            $table->decimal('total_deducted_usd', 14, 2)->default(0);
            $table->decimal('bdt_cost', 16, 2)->default(0);
            $table->decimal('client_revenue', 16, 2)->default(0);
            $table->decimal('net_profit', 16, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_reports');
        Schema::dropIfExists('card_transactions');
        Schema::dropIfExists('card_loads');
        Schema::dropIfExists('binance_purchases');
    }
};
