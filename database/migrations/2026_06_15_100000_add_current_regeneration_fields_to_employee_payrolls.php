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
            if (! Schema::hasColumn('employee_payrolls', 'is_current')) {
                $table->boolean('is_current')->default(true)->after('generation_status');
            }

            if (! Schema::hasColumn('employee_payrolls', 'superseded_by_id')) {
                $table->foreignId('superseded_by_id')->nullable()->after('is_current')->constrained('employee_payrolls')->nullOnDelete();
            }
        });

        DB::table('employee_payrolls')
            ->whereNotNull('regenerated_from_id')
            ->orderBy('id')
            ->get()
            ->each(function ($payroll) {
                DB::table('employee_payrolls')
                    ->where('id', $payroll->regenerated_from_id)
                    ->update([
                        'is_current' => false,
                        'superseded_by_id' => $payroll->id,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('employee_payrolls', 'superseded_by_id')) {
                $table->dropConstrainedForeignId('superseded_by_id');
            }

            if (Schema::hasColumn('employee_payrolls', 'is_current')) {
                $table->dropColumn('is_current');
            }
        });
    }
};
