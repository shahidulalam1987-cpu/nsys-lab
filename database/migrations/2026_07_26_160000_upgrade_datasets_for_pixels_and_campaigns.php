<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            if (! Schema::hasColumn('datasets', 'business_manager_id')) {
                $table->foreignId('business_manager_id')->nullable()->after('dataset_id')->constrained('business_managers')->nullOnDelete();
            }
            if (! Schema::hasColumn('datasets', 'event_source_type')) {
                $table->string('event_source_type')->default('website')->after('platform');
            }
            if (! Schema::hasColumn('datasets', 'domain_url')) {
                $table->string('domain_url')->nullable()->after('event_source_type');
            }
        });

        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'dataset_id')) {
                $table->foreignId('dataset_id')->nullable()->after('client_page_id')->constrained('datasets')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'dataset_id')) {
                $table->dropConstrainedForeignId('dataset_id');
            }
        });

        Schema::table('datasets', function (Blueprint $table) {
            if (Schema::hasColumn('datasets', 'business_manager_id')) {
                $table->dropConstrainedForeignId('business_manager_id');
            }
            if (Schema::hasColumn('datasets', 'event_source_type')) {
                $table->dropColumn('event_source_type');
            }
            if (Schema::hasColumn('datasets', 'domain_url')) {
                $table->dropColumn('domain_url');
            }
        });
    }
};
