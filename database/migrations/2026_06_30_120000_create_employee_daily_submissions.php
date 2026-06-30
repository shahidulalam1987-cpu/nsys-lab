<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_daily_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bm_id')->nullable()->constrained('business_managers')->nullOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('submission_date');
            $table->string('submission_type');
            $table->unsignedInteger('orders')->nullable();
            $table->unsignedInteger('confirmed_orders')->nullable();
            $table->unsignedInteger('cancelled_orders')->nullable();
            $table->decimal('dollar_spend', 14, 2)->nullable();
            $table->decimal('cpm', 14, 2)->nullable();
            $table->decimal('cpc', 14, 2)->nullable();
            $table->decimal('ctr', 8, 4)->nullable();
            $table->string('screenshot_path')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('submission_key')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['employee_id', 'submission_date', 'submission_type', 'page_id', 'campaign_id'],
                'employee_daily_submissions_scope_unique'
            );
            $table->index(['submission_date', 'submission_type']);
        });

        Schema::table('daily_performance_reports', function (Blueprint $table) {
            $table->string('status')->default('manual')->after('report_date')->index();
        });
    }

    public function down(): void
    {
        Schema::table('daily_performance_reports', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::dropIfExists('employee_daily_submissions');
    }
};
