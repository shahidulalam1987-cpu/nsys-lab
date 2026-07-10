<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_operation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (Schema::hasTable('moderator_reports') && ! Schema::hasColumn('moderator_reports', 'returned_orders')) {
            Schema::table('moderator_reports', function (Blueprint $table) {
                $table->unsignedInteger('returned_orders')->default(0)->after('pending_orders');
            });
        }

        $this->seedDefaults();
        $this->seedPermissionAssignments();
    }

    public function down(): void
    {
        if (Schema::hasTable('moderator_reports') && Schema::hasColumn('moderator_reports', 'returned_orders')) {
            Schema::table('moderator_reports', function (Blueprint $table) {
                $table->dropColumn('returned_orders');
            });
        }

        Schema::dropIfExists('marketing_operation_settings');
    }

    private function seedDefaults(): void
    {
        $defaults = [
            'timezone' => ['Asia/Dhaka', 'string', 'Marketing operations timezone.'],
            'moderator_submission_start' => ['01:00', 'time', 'Moderator daily submission start time.'],
            'moderator_submission_end' => ['02:00', 'time', 'Moderator daily submission deadline.'],
            'ad_manager_submission_start' => ['01:00', 'time', 'Ad manager daily submission start time.'],
            'ad_manager_submission_end' => ['02:00', 'time', 'Ad manager daily submission deadline.'],
            'auditor_review_start' => ['02:00', 'time', 'Auditor review start time.'],
            'auditor_review_end' => ['08:00', 'time', 'Auditor review deadline.'],
            'monitor_review_start' => ['08:00', 'time', 'Monitor review start time.'],
            'monitor_review_end' => ['11:00', 'time', 'Monitor review deadline.'],
            'agency_review_start' => ['11:00', 'time', 'Agency operations review start time.'],
            'agency_review_end' => ['13:00', 'time', 'Agency operations review deadline.'],
            'late_submission_buffer_minutes' => ['1', 'integer', 'Minutes after deadline when reports become late.'],
            'missing_report_buffer_minutes' => ['30', 'integer', 'Minutes after deadline when missing reports are counted.'],
            'reminder_before_open_minutes' => ['10', 'integer', 'Reminder before submission window opens.'],
            'reminder_before_close_minutes' => ['15,5', 'csv', 'Reminder minutes before submission window closes.'],
        ];

        $now = now();
        foreach ($defaults as $key => [$value, $type, $description]) {
            DB::table('marketing_operation_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'type' => $type, 'description' => $description, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    private function seedPermissionAssignments(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissionKeys = [
            'marketing_operations.view',
            'marketing_operations.manage',
            'marketing_operations.submit',
            'marketing_operations.verify',
            'marketing_operations.approve',
            'marketing_operations.agency',
        ];
        $permissionIds = DB::table('permissions')->whereIn('key', $permissionKeys)->pluck('id', 'key');
        $roleIds = DB::table('roles')->pluck('id', 'slug');

        foreach (['super_admin', 'facebook_manager'] as $role) {
            if (! isset($roleIds[$role])) {
                continue;
            }
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleIds[$role], 'permission_id' => $permissionId]);
            }
        }

        foreach (['moderator', 'employee'] as $role) {
            if (! isset($roleIds[$role])) {
                continue;
            }
            foreach (['marketing_operations.view', 'marketing_operations.submit'] as $permission) {
                if (isset($permissionIds[$permission])) {
                    DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleIds[$role], 'permission_id' => $permissionIds[$permission]]);
                }
            }
        }
    }
};
