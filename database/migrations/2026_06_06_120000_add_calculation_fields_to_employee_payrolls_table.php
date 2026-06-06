<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->string('calculation_type')->default('date_to_date')->after('client_id');
            $table->date('salary_period_from')->nullable()->after('calculation_type');
            $table->date('salary_period_to')->nullable()->after('salary_period_from');
            $table->unsignedSmallInteger('month_days')->nullable()->after('non_working_days');
            $table->decimal('daily_salary', 12, 2)->nullable()->after('month_days');

            $table->index(['calculation_type', 'salary_period_from', 'salary_period_to'], 'employee_payrolls_calc_period_index');
        });

        DB::table('employee_payrolls')
            ->whereNull('salary_period_from')
            ->update([
                'salary_period_from' => DB::raw('from_date'),
                'salary_period_to' => DB::raw('to_date'),
            ]);
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->dropIndex('employee_payrolls_calc_period_index');
            $table->dropColumn([
                'calculation_type',
                'salary_period_from',
                'salary_period_to',
                'month_days',
                'daily_salary',
            ]);
        });
    }
};
