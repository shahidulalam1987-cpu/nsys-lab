<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->after('status');
            $table->string('payment_proof')->nullable()->after('payment_status');
            $table->string('transaction_id')->nullable()->after('payment_proof');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_proof',
                'transaction_id',
            ]);
        });
    }
};
