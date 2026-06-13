<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_payrolls', 'payroll_employee_name')) {
                $table->string('payroll_employee_name')->nullable()->after('employee_id');
            }

            if (! Schema::hasColumn('employee_payrolls', 'payroll_employee_code')) {
                $table->string('payroll_employee_code')->nullable()->after('payroll_employee_name');
            }

            if (! Schema::hasColumn('employee_payrolls', 'payroll_salary_amount')) {
                $table->decimal('payroll_salary_amount', 12, 2)->nullable()->after('payable_salary');
            }

            if (! Schema::hasColumn('employee_payrolls', 'finance_account_id')) {
                $table->foreignId('finance_account_id')->nullable()->after('payment_method')->constrained('finance_accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_payrolls', 'finance_account_name')) {
                $table->string('finance_account_name')->nullable()->after('finance_account_id');
            }

            if (! Schema::hasColumn('employee_payrolls', 'payment_note')) {
                $table->text('payment_note')->nullable()->after('transaction_id');
            }

            if (! Schema::hasColumn('employee_payrolls', 'salary_payment_attachment')) {
                $table->string('salary_payment_attachment')->nullable()->after('payment_note');
            }

            if (! Schema::hasColumn('employee_payrolls', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('salary_payment_attachment');
            }

            if (! Schema::hasColumn('employee_payrolls', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('payment_confirmed_at');
            }

            if (! Schema::hasColumn('employee_payrolls', 'reversed_by')) {
                $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
            }

            if (! Schema::hasColumn('employee_payrolls', 'reversal_note')) {
                $table->text('reversal_note')->nullable()->after('reversed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            foreach ([
                'reversal_note',
                'reversed_by',
                'reversed_at',
                'payment_confirmed_at',
                'salary_payment_attachment',
                'payment_note',
                'finance_account_name',
                'finance_account_id',
                'payroll_salary_amount',
                'payroll_employee_code',
                'payroll_employee_name',
            ] as $column) {
                if (Schema::hasColumn('employee_payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
