<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'employee_type')) {
                $table->string('employee_type')->default('client_assigned')->after('user_id');
            }

            if (! Schema::hasColumn('employees', 'salary_source')) {
                $table->string('salary_source')->default('client_fund')->after('monthly_salary');
            }

            if (! Schema::hasColumn('employees', 'permission_group')) {
                $table->string('permission_group')->nullable()->after('salary_source');
            }
        });

        Schema::table('employee_payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_payrolls', 'salary_source')) {
                $table->string('salary_source')->default('client_fund')->after('client_id');
            }
        });

        DB::table('employees')->whereNull('employee_type')->update(['employee_type' => 'client_assigned']);
        DB::table('employees')->whereNull('salary_source')->update(['salary_source' => 'client_fund']);
        DB::table('employee_payrolls')->whereNull('salary_source')->update(['salary_source' => 'client_fund']);
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('employee_payrolls', 'salary_source')) {
                $table->dropColumn('salary_source');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            foreach (['permission_group', 'salary_source', 'employee_type'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
