<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('group_key')->unique();
            $table->date('performance_date')->index();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('partial')->index();
            $table->text('admin_note')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('daily_performance_reports', function (Blueprint $table) {
            $table->foreignId('merged_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('merged_at')->nullable()->after('merged_by');
            $table->json('source_submission_ids')->nullable()->after('merged_at');
        });

        Schema::create('employee_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('employee_roles')->nullOnDelete();
            $table->string('target_type');
            $table->decimal('target_value', 14, 2);
            $table->string('period_type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bonus_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('applies_to_type');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('employee_roles')->nullOnDelete();
            $table->string('metric');
            $table->string('comparison')->default('gte');
            $table->decimal('threshold', 14, 2);
            $table->decimal('bonus_amount', 14, 2);
            $table->string('bonus_type');
            $table->string('period_type');
            $table->string('status')->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_bonus_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bonus_rule_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('metric_value', 14, 2);
            $table->decimal('bonus_amount', 14, 2);
            $table->string('status')->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_payroll_id')->nullable()->constrained('employee_payrolls')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'bonus_rule_id', 'period_start', 'period_end'], 'employee_bonus_period_unique');
        });

        $this->addPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $keys = ['performance.view', 'performance.manage', 'performance.approve', 'performance.merge', 'kpi.view', 'leaderboard.view', 'targets.manage', 'bonus.view', 'bonus.manage', 'bonus.approve'];
            $ids = DB::table('permissions')->whereIn('key', $keys)->pluck('id');
            DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        Schema::dropIfExists('employee_bonus_earnings');
        Schema::dropIfExists('bonus_rules');
        Schema::dropIfExists('employee_targets');
        Schema::table('daily_performance_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merged_by');
            $table->dropColumn(['merged_at', 'source_submission_ids']);
        });
        Schema::dropIfExists('performance_verifications');
    }

    private function addPermissions(): void
    {
        $permissions = [
            'performance.view' => 'Performance View',
            'performance.manage' => 'Performance Manage',
            'performance.approve' => 'Performance Approve',
            'performance.merge' => 'Performance Merge',
            'kpi.view' => 'KPI View',
            'leaderboard.view' => 'Leaderboard View',
            'targets.manage' => 'Targets Manage',
            'bonus.view' => 'Bonus View',
            'bonus.manage' => 'Bonus Manage',
            'bonus.approve' => 'Bonus Approve',
        ];
        $now = now();
        foreach ($permissions as $key => $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'key' => $key, 'module' => 'performance', 'created_at' => $now, 'updated_at' => $now]);
        }

        $ids = DB::table('permissions')->whereIn('key', array_keys($permissions))->pluck('id', 'key');
        $roles = DB::table('roles')->whereIn('slug', ['super_admin', 'facebook_manager', 'hr_manager', 'finance_manager'])->pluck('id', 'slug');
        $grants = [
            'super_admin' => array_keys($permissions),
            'facebook_manager' => ['performance.view', 'performance.manage', 'performance.approve', 'performance.merge', 'leaderboard.view'],
            'hr_manager' => ['kpi.view', 'leaderboard.view', 'targets.manage', 'bonus.view', 'bonus.manage', 'bonus.approve'],
            'finance_manager' => ['bonus.view'],
        ];
        foreach ($grants as $role => $keys) {
            foreach ($keys as $key) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roles[$role], 'permission_id' => $ids[$key]]);
            }
        }
    }
};
