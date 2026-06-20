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
            $table->unsignedBigInteger('finance_account_id')->nullable()->change();
            $table->foreign('finance_account_id')->references('id')->on('finance_accounts')->restrictOnDelete();
            $table->string('currency', 3)->default('BDT')->after('amount');
            $table->string('direction', 10)->nullable()->after('currency');
            $table->string('reference_type')->nullable()->after('reference');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->decimal('old_balance', 16, 2)->nullable()->after('reference_id');
            $table->decimal('new_balance_snapshot', 16, 2)->nullable()->after('old_balance');
            $table->text('description')->nullable()->after('new_balance_snapshot');
            $table->string('transaction_reference')->nullable()->after('description');
            $table->index(['reference_type', 'reference_id'], 'finance_ledgers_reference_index');
        });

        Schema::table('salary_payments', function (Blueprint $table) {
            $table->foreignId('finance_account_id')->nullable()->after('client_id')->constrained('finance_accounts')->nullOnDelete();
        });

        Schema::table('binance_purchases', function (Blueprint $table) {
            $table->foreignId('finance_account_id')->nullable()->after('id')->constrained('finance_accounts')->nullOnDelete();
        });

        Schema::table('card_loads', function (Blueprint $table) {
            $table->decimal('fee_usd', 14, 2)->default(0)->after('usd_loaded');
            $table->string('transaction_reference')->nullable()->after('fee_usd');
        });

        Schema::table('card_transactions', function (Blueprint $table) {
            $table->string('transaction_reference')->nullable()->after('extra_charge_usd');
        });

        Schema::table('finance_loans', function (Blueprint $table) {
            $table->foreignId('finance_account_id')->nullable()->after('loan_type')->constrained('finance_accounts')->nullOnDelete();
        });

        Schema::table('finance_loan_repayments', function (Blueprint $table) {
            $table->foreignId('finance_account_id')->nullable()->after('finance_loan_id')->constrained('finance_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_loan_repayments', fn (Blueprint $table) => $table->dropConstrainedForeignId('finance_account_id'));
        Schema::table('finance_loans', fn (Blueprint $table) => $table->dropConstrainedForeignId('finance_account_id'));
        Schema::table('card_transactions', fn (Blueprint $table) => $table->dropColumn('transaction_reference'));
        Schema::table('card_loads', fn (Blueprint $table) => $table->dropColumn(['fee_usd', 'transaction_reference']));
        Schema::table('binance_purchases', fn (Blueprint $table) => $table->dropConstrainedForeignId('finance_account_id'));
        Schema::table('salary_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('finance_account_id'));

        Schema::table('finance_account_ledgers', function (Blueprint $table) {
            $table->dropIndex('finance_ledgers_reference_index');
            $table->dropColumn(['currency', 'direction', 'reference_type', 'reference_id', 'old_balance', 'new_balance_snapshot', 'description', 'transaction_reference']);
            $table->dropForeign(['finance_account_id']);
            $table->unsignedBigInteger('finance_account_id')->nullable(false)->change();
            $table->foreign('finance_account_id')->references('id')->on('finance_accounts')->restrictOnDelete();
        });
    }
};
