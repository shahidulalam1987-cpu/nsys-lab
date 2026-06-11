<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ad_accounts')
            ->whereNull('currency')
            ->orWhere('currency', '')
            ->update(['currency' => 'USD']);

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ad_accounts ALTER COLUMN currency SET DEFAULT 'USD'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ad_accounts ALTER COLUMN currency SET DEFAULT 'BDT'");
        }
    }
};
