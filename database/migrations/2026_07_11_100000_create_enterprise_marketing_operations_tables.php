<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderator_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('client_pages')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->date('submission_date');
            $table->unsignedInteger('orders')->default(0);
            $table->unsignedInteger('confirmed_orders')->default(0);
            $table->unsignedInteger('cancelled_orders')->default(0);
            $table->unsignedInteger('pending_orders')->default(0);
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['page_id', 'submission_date'], 'moderator_reports_page_date_unique');
            $table->index(['client_id', 'submission_date']);
        });

        Schema::create('ad_manager_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->date('report_date');
            $table->decimal('spend_usd', 14, 2)->default(0);
            $table->decimal('spend_bdt', 14, 2)->default(0);
            $table->unsignedInteger('purchases')->default(0);
            $table->decimal('cpp', 14, 2)->default(0);
            $table->decimal('cpm', 14, 2)->nullable();
            $table->decimal('ctr', 8, 4)->nullable();
            $table->decimal('cpc', 14, 2)->nullable();
            $table->decimal('frequency', 8, 2)->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('impressions')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['campaign_id', 'report_date'], 'ad_manager_reports_campaign_date_unique');
            $table->index(['client_id', 'report_date']);
        });

        Schema::create('auditor_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('client_pages')->cascadeOnDelete();
            $table->foreignId('moderator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->date('audit_date');
            $table->decimal('average_response_time', 10, 2)->default(0);
            $table->decimal('longest_delay', 10, 2)->default(0);
            $table->unsignedInteger('total_delayed_replies')->default(0);
            $table->decimal('qa_score', 8, 2)->default(0);
            $table->decimal('message_quality', 8, 2)->default(0);
            $table->decimal('greeting_score', 8, 2)->default(0);
            $table->decimal('closing_score', 8, 2)->default(0);
            $table->decimal('follow_up_score', 8, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('overall_status')->default('average')->index();
            $table->string('status')->default('draft')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['page_id', 'moderator_id', 'audit_date'], 'auditor_reports_page_moderator_date_unique');
        });

        Schema::create('monitor_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->foreignId('reporter_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('review_date');
            $table->string('issue_type');
            $table->text('description');
            $table->string('severity')->default('medium')->index();
            $table->text('recommendation')->nullable();
            $table->string('resolution_status')->default('pending')->index();
            $table->string('screenshot_path')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['severity', 'resolution_status']);
        });

        Schema::create('agency_operation_reviews', function (Blueprint $table) {
            $table->id();
            $table->date('review_date')->index();
            $table->unsignedInteger('today_orders')->default(0);
            $table->decimal('today_spend', 14, 2)->default(0);
            $table->decimal('today_revenue', 14, 2)->default(0);
            $table->decimal('today_estimated_profit', 14, 2)->default(0);
            $table->unsignedInteger('pending_reports')->default(0);
            $table->unsignedInteger('pending_verifications')->default(0);
            $table->json('alerts')->nullable();
            $table->string('final_status')->default('pending')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('page_daily_operation_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('moderator_report_id')->nullable()->constrained('moderator_reports')->nullOnDelete();
            $table->foreignId('ad_manager_report_id')->nullable()->constrained('ad_manager_reports')->nullOnDelete();
            $table->foreignId('auditor_report_id')->nullable()->constrained('auditor_reports')->nullOnDelete();
            $table->foreignId('monitor_report_id')->nullable()->constrained('monitor_reports')->nullOnDelete();
            $table->unsignedInteger('orders')->default(0);
            $table->decimal('spend_usd', 14, 2)->default(0);
            $table->decimal('cpp', 14, 2)->default(0);
            $table->decimal('revenue', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->string('final_status')->default('pending')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['summary_date', 'page_id', 'campaign_id'], 'page_daily_ops_summary_unique');
        });

        $this->addPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('page_daily_operation_summaries');
        Schema::dropIfExists('agency_operation_reviews');
        Schema::dropIfExists('monitor_reports');
        Schema::dropIfExists('auditor_reports');
        Schema::dropIfExists('ad_manager_reports');
        Schema::dropIfExists('moderator_reports');
    }

    private function addPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            'marketing_operations.verify' => 'Marketing Operations Verify',
            'marketing_operations.approve' => 'Marketing Operations Approve',
            'marketing_operations.agency' => 'Marketing Operations Agency',
        ];

        $now = now();
        foreach ($permissions as $key => $name) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                ['name' => $name, 'key' => $key, 'module' => 'marketing_operations', 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
};
