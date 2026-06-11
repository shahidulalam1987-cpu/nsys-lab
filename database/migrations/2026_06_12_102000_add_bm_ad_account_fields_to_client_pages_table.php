<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_pages', function (Blueprint $table) {
            $table->string('page_id')->nullable()->unique()->after('page_name');
            $table->foreignId('business_manager_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->foreignId('ad_account_id')->nullable()->after('business_manager_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ad_account_id');
            $table->dropConstrainedForeignId('business_manager_id');
            $table->dropUnique(['page_id']);
            $table->dropColumn('page_id');
        });
    }
};
