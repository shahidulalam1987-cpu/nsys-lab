<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name');
            $table->string('campaign_id')->unique();
            $table->foreignId('business_manager_id')->constrained()->restrictOnDelete();
            $table->foreignId('ad_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_page_id')->constrained('client_pages')->restrictOnDelete();
            $table->string('objective');
            $table->string('status')->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('daily_budget', 14, 2)->default(0);
            $table->decimal('lifetime_budget', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_manager_id', 'ad_account_id']);
            $table->index(['client_id', 'client_page_id']);
            $table->index(['status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
