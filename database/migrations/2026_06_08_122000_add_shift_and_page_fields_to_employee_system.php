<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('role')->constrained('shifts')->nullOnDelete();
            }
        });

        Schema::table('employee_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_assignments', 'client_page_id')) {
                $table->foreignId('client_page_id')->nullable()->after('client_id')->constrained('client_pages')->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_assignments', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('client_page_id')->constrained('shifts')->nullOnDelete();
            }
        });

        Schema::table('employee_work_statuses', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_work_statuses', 'client_page_id')) {
                $table->foreignId('client_page_id')->nullable()->after('client_id')->constrained('client_pages')->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_work_statuses', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('client_page_id')->constrained('shifts')->nullOnDelete();
            }
        });

        Schema::table('employee_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_attendances', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('client_id')->constrained('shifts')->nullOnDelete();
            }

            if (! Schema::hasColumn('employee_attendances', 'is_late')) {
                $table->boolean('is_late')->default(false)->after('check_in_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_attendances', function (Blueprint $table) {
            foreach (['is_late', 'shift_id'] as $column) {
                if (Schema::hasColumn('employee_attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('employee_work_statuses', function (Blueprint $table) {
            foreach (['shift_id', 'client_page_id'] as $column) {
                if (Schema::hasColumn('employee_work_statuses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('employee_assignments', function (Blueprint $table) {
            foreach (['shift_id', 'client_page_id'] as $column) {
                if (Schema::hasColumn('employee_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'shift_id')) {
                $table->dropColumn('shift_id');
            }
        });
    }
};
