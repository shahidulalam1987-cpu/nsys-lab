<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('binance_purchases', function (Blueprint $table) {
            $table->decimal('remaining_usd', 14, 2)->nullable()->after('usd_amount');
        });

        Schema::table('card_transactions', function (Blueprint $table) {
            $table->decimal('extra_charge_usd', 14, 2)->default(0)->after('fee_usd');
        });

        DB::table('binance_purchases')
            ->orderBy('id')
            ->get()
            ->each(function ($purchase) {
                $loaded = (float) DB::table('card_loads')
                    ->where('binance_purchase_id', $purchase->id)
                    ->sum('usd_loaded');

                DB::table('binance_purchases')
                    ->where('id', $purchase->id)
                    ->update([
                        'remaining_usd' => max(round((float) $purchase->usd_amount - $loaded, 2), 0),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('card_transactions', function (Blueprint $table) {
            $table->dropColumn('extra_charge_usd');
        });

        Schema::table('binance_purchases', function (Blueprint $table) {
            $table->dropColumn('remaining_usd');
        });
    }
};
