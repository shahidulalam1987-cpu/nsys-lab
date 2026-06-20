<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('role_name')->nullable()->after('user_id');
            $table->json('old_value')->nullable()->after('description');
            $table->json('new_value')->nullable()->after('old_value');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', fn (Blueprint $table) => $table->dropColumn(['role_name', 'old_value', 'new_value']));
    }
};
