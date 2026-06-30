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
            $table->date('cycle_due_date')->nullable()->after('salary_month')->index();
            $table->string('cycle_key')->nullable()->after('cycle_due_date')->unique();
            $table->boolean('is_final_settlement')->default(false)->after('cycle_key')->index();
        });

        $employees = DB::table('employees')
            ->where('status', 'terminated')
            ->whereNotNull('last_working_date')
            ->get(['id', 'last_working_date']);

        foreach ($employees as $employee) {
            $lastWorkingDate = substr((string) $employee->last_working_date, 0, 10);
            $lastWorkingMonth = substr($lastWorkingDate, 0, 7);

            DB::table('employee_payrolls')
                ->where('employee_id', $employee->id)
                ->where(function ($query) use ($lastWorkingDate, $lastWorkingMonth) {
                    $query->where(function ($query) use ($lastWorkingDate) {
                        $query->whereDate('salary_period_from', '<=', $lastWorkingDate)
                            ->whereDate('salary_period_to', '>=', $lastWorkingDate);
                    })->orWhereRaw('substr(salary_month, 1, 7) = ?', [$lastWorkingMonth]);
                })
                ->update(['is_final_settlement' => true]);

            $hasFinalSettlement = DB::table('employee_payrolls')
                ->where('employee_id', $employee->id)
                ->where('is_final_settlement', true)
                ->exists();

            if (! $hasFinalSettlement) {
                $latestCurrentPayrollId = DB::table('employee_payrolls')
                    ->where('employee_id', $employee->id)
                    ->where(function ($query) {
                        $query->where('is_current', true)->orWhereNull('is_current');
                    })
                    ->latest('id')
                    ->value('id');

                if ($latestCurrentPayrollId) {
                    DB::table('employee_payrolls')
                        ->where('id', $latestCurrentPayrollId)
                        ->update(['is_final_settlement' => true]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->dropUnique(['cycle_key']);
            $table->dropIndex(['cycle_due_date']);
            $table->dropIndex(['is_final_settlement']);
            $table->dropColumn(['cycle_due_date', 'cycle_key', 'is_final_settlement']);
        });
    }
};
