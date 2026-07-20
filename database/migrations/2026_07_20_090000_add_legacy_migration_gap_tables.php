<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code')->unique();
            $table->string('name');
            $table->string('provider_type')->default('card_wallet');
            $table->string('currency')->default('USD');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ad_account_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_page_id')->constrained('client_pages')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->date('mapped_from')->nullable();
            $table->date('mapped_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ad_account_id', 'client_page_id', 'deleted_at'], 'ad_account_pages_unique_active');
        });

        Schema::create('ad_account_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facebook_card_id')->constrained('facebook_cards')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active');
            $table->date('mapped_from')->nullable();
            $table->date('mapped_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ad_account_id', 'facebook_card_id', 'deleted_at'], 'ad_account_cards_unique_active');
        });

        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
            $table->string('dataset_name');
            $table->string('dataset_id')->unique();
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->string('platform')->default('Meta');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('provider_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('facebook_card_id')->nullable()->constrained('facebook_cards')->nullOnDelete();
            $table->date('transaction_date');
            $table->string('transaction_type')->default('manual');
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->decimal('fee_usd', 14, 2)->default(0);
            $table->string('reference')->nullable();
            $table->string('status')->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('provider_fee_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('facebook_card_id')->nullable()->constrained('facebook_cards')->nullOnDelete();
            $table->date('sample_date');
            $table->decimal('facebook_charge_usd', 14, 2)->default(0);
            $table->decimal('provider_deducted_usd', 14, 2)->default(0);
            $table->decimal('fee_amount_usd', 14, 2)->default(0);
            $table->decimal('fee_percentage', 8, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ad_account_billing_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->date('billing_date');
            $table->decimal('billing_amount_usd', 14, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('meta_spend_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->date('snapshot_date');
            $table->decimal('spend_usd', 14, 2)->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->json('raw_payload')->nullable();
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->unique(['campaign_id', 'snapshot_date', 'source'], 'meta_snapshot_campaign_date_source_unique');
        });

        Schema::create('whats_app_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('loggable');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient')->nullable();
            $table->string('message_type')->default('daily_report');
            $table->string('status')->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('response')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('meta_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type')->default('spend');
            $table->string('status')->default('pending');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('records_processed')->default(0);
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_sync_logs');
        Schema::dropIfExists('whats_app_logs');
        Schema::dropIfExists('meta_spend_snapshots');
        Schema::dropIfExists('ad_account_billing_history');
        Schema::dropIfExists('provider_fee_tracking');
        Schema::dropIfExists('provider_transactions');
        Schema::dropIfExists('datasets');
        Schema::dropIfExists('ad_account_cards');
        Schema::dropIfExists('ad_account_pages');
        Schema::dropIfExists('payment_providers');
    }
};
