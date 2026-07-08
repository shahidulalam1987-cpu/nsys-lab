<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_operations_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->string('platform')->default('Meta');
            $table->date('report_date');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('target_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('page_id')->nullable()->constrained('client_pages')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('ad_account_id')->nullable()->constrained('ad_accounts')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('employee_roles')->nullOnDelete();
            $table->json('metrics')->nullable();
            $table->text('notes')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('severity')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('duplicate_key')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_type', 'status']);
            $table->index(['report_date', 'platform']);
            $table->index(['client_id', 'page_id', 'campaign_id']);
        });

        if (Schema::hasTable('permissions')) {
            $now = now();
            $permissions = [
                'marketing_operations.view' => ['Marketing Operations View', 'marketing_operations'],
                'marketing_operations.manage' => ['Marketing Operations Manage', 'marketing_operations'],
                'marketing_operations.submit' => ['Marketing Operations Submit', 'marketing_operations'],
            ];

            foreach ($permissions as $key => [$name, $module]) {
                DB::table('permissions')->updateOrInsert(
                    ['key' => $key],
                    compact('name', 'key', 'module') + ['updated_at' => $now, 'created_at' => $now]
                );
            }

            $permissionIds = DB::table('permissions')->whereIn('key', array_keys($permissions))->pluck('id', 'key');
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
                if (isset($roleIds[$role], $permissionIds['marketing_operations.submit'])) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id' => $roleIds[$role],
                        'permission_id' => $permissionIds['marketing_operations.submit'],
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_operations_reports');
    }
};
