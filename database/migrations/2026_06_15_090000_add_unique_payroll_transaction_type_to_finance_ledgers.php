<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_account_ledgers', function (Blueprint $table) {
            $table->unique(['employee_payroll_id', 'transaction_type'], 'finance_ledgers_payroll_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('finance_account_ledgers', function (Blueprint $table) {
            $table->dropUnique('finance_ledgers_payroll_type_unique');
        });
    }
};
