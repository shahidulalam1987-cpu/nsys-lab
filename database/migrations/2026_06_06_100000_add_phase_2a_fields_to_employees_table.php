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
            $table->string('email')->nullable()->after('mobile');
            $table->text('address')->nullable()->after('email');
            $table->string('nid_number')->nullable()->after('address');
            $table->date('date_of_birth')->nullable()->after('nid_number');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->unsignedTinyInteger('salary_day')->nullable()->after('salary_type');
            $table->string('branch_name')->nullable()->after('account_number');
            $table->string('bkash_number')->nullable()->after('branch_name');
            $table->string('nagad_number')->nullable()->after('bkash_number');
            $table->string('rocket_number')->nullable()->after('nagad_number');
            $table->string('preferred_payment_method')->nullable()->after('rocket_number');
            $table->text('admin_note')->nullable()->after('mobile_banking_info');
        });

        DB::table('employees')
            ->where('status', 'suspended')
            ->update(['status' => 'inactive']);

        DB::table('employees')
            ->whereNull('salary_day')
            ->whereNotNull('confirmation_date')
            ->orderBy('id')
            ->get(['id', 'confirmation_date'])
            ->each(function ($employee) {
                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update([
                        'salary_day' => (int) date('j', strtotime($employee->confirmation_date)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'address',
                'nid_number',
                'date_of_birth',
                'gender',
                'salary_day',
                'branch_name',
                'bkash_number',
                'nagad_number',
                'rocket_number',
                'preferred_payment_method',
                'admin_note',
            ]);
        });
    }
};
