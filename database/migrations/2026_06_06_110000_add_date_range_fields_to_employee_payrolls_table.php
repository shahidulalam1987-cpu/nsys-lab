<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->date('from_date')->nullable()->after('client_id');
            $table->date('to_date')->nullable()->after('from_date');
            $table->unsignedSmallInteger('working_days')->nullable()->after('to_date');
            $table->unsignedSmallInteger('non_working_days')->nullable()->after('working_days');

            $table->index(['from_date', 'to_date']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->dropIndex(['from_date', 'to_date']);
            $table->dropColumn([
                'from_date',
                'to_date',
                'working_days',
                'non_working_days',
            ]);
        });
    }
};
