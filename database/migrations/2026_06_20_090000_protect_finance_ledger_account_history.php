<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_account_ledgers', function (Blueprint $table) {
            $table->dropForeign(['finance_account_id']);
            $table->foreign('finance_account_id')
                ->references('id')
                ->on('finance_accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_account_ledgers', function (Blueprint $table) {
            $table->dropForeign(['finance_account_id']);
            $table->foreign('finance_account_id')
                ->references('id')
                ->on('finance_accounts')
                ->cascadeOnDelete();
        });
    }
};
