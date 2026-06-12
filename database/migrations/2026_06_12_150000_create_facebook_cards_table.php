<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_name');
            $table->string('card_type')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('provider')->nullable();
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_cards');
    }
};
