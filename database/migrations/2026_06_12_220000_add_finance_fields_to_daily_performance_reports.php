<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_performance_reports', function (Blueprint $table) {
            $table->string('card_provider')->nullable()->after('orders');
            $table->decimal('fee_usd', 14, 2)->default(0)->after('card_provider');
            $table->decimal('extra_charge_usd', 14, 2)->default(0)->after('fee_usd');
        });
    }

    public function down(): void
    {
        Schema::table('daily_performance_reports', function (Blueprint $table) {
            $table->dropColumn(['card_provider', 'fee_usd', 'extra_charge_usd']);
        });
    }
};
