<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_payrolls', 'payroll_bank_name')) {
                $table->string('payroll_bank_name')->nullable()->after('paid_amount');
            }

            if (! Schema::hasColumn('employee_payrolls', 'payroll_account_name')) {
                $table->string('payroll_account_name')->nullable()->after('payroll_bank_name');
            }

            if (! Schema::hasColumn('employee_payrolls', 'payroll_account_number')) {
                $table->string('payroll_account_number')->nullable()->after('payroll_account_name');
            }

            if (! Schema::hasColumn('employee_payrolls', 'payroll_branch_name')) {
                $table->string('payroll_branch_name')->nullable()->after('payroll_account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            foreach ([
                'payroll_branch_name',
                'payroll_account_number',
                'payroll_account_name',
                'payroll_bank_name',
            ] as $column) {
                if (Schema::hasColumn('employee_payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
