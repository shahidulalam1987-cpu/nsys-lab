<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('client_page_id')->constrained()->nullOnDelete();
        });

        Schema::table('employee_work_statuses', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('client_page_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_work_statuses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
        });

        Schema::table('employee_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
        });
    }
};
