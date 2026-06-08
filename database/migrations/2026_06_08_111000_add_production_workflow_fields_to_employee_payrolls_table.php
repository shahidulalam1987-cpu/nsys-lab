<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_payrolls', 'payroll_status')) {
                $table->string('payroll_status')->default('generated')->after('payment_status');
            }

            if (! Schema::hasColumn('employee_payrolls', 'generation_status')) {
                $table->string('generation_status')->default('generated')->after('payroll_status');
            }

            if (! Schema::hasColumn('employee_payrolls', 'regenerated_from_id')) {
                $table->unsignedBigInteger('regenerated_from_id')->nullable()->after('generation_status');
            }

            if (! Schema::hasColumn('employee_payrolls', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('regenerated_from_id');
            }

            if (! Schema::hasColumn('employee_payrolls', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('employee_payrolls', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('employee_payrolls', 'paid_by')) {
                $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            foreach ([
                'paid_by',
                'paid_at',
                'approved_by',
                'approved_at',
                'regenerated_from_id',
                'generation_status',
                'payroll_status',
            ] as $column) {
                if (Schema::hasColumn('employee_payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
