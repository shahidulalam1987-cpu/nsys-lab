<?php

use App\Services\PermissionRouteRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();
        $roles = [
            'agency_owner' => 'Agency Owner',
            'agency_operations_manager' => 'Agency Operations Manager',
            'ad_manager' => 'Ad Manager',
            'auditor' => 'Auditor',
            'monitor' => 'Monitor',
            'trainer' => 'Trainer',
            'business_manager' => 'Business Manager',
            'page_manager' => 'Page Manager',
        ];

        foreach ($roles as $slug => $name) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'description' => $name.' access group.', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $registry = app(PermissionRouteRegistry::class);
        foreach ($registry->permissionCatalog() as $key => [$name, $module]) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                ['name' => $name, 'module' => $module, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $permissionIds = DB::table('permissions')->pluck('id', 'key');
        $roleIds = DB::table('roles')->pluck('id', 'slug');

        if (isset($roleIds['super_admin'])) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleIds['super_admin'],
                    'permission_id' => $permissionId,
                ]);
            }
        }

        foreach ($registry->roleDefaults() as $role => $keys) {
            if (! isset($roleIds[$role])) {
                continue;
            }

            foreach ($keys as $key) {
                if (! isset($permissionIds[$key])) {
                    continue;
                }

                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleIds[$role],
                    'permission_id' => $permissionIds[$key],
                ]);
            }
        }

        $legacyMap = [
            'dashboard.view' => 'admin_dashboard.view',
            'client_fund.view' => 'client_funds.view',
            'client_fund.manage' => 'client_funds.manage',
            'employee_roles.view' => 'roles.manage',
            'employee_roles.manage' => 'roles.manage',
            'daily_reports.view' => 'daily_performance.view',
            'daily_reports.manage' => 'daily_performance.manage',
            'performance.view' => 'agency_operations.verify',
            'performance.manage' => 'agency_operations.verify',
            'performance.approve' => 'agency_operations.approve',
            'performance.merge' => 'agency_operations.approve',
        ];

        foreach ($legacyMap as $legacy => $modern) {
            if (! isset($permissionIds[$legacy], $permissionIds[$modern])) {
                continue;
            }

            $roleIdsWithLegacy = DB::table('role_permissions')
                ->where('permission_id', $permissionIds[$legacy])
                ->pluck('role_id');

            foreach ($roleIdsWithLegacy as $roleId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionIds[$modern],
                ]);
            }
        }

        $this->copyLegacyFacebookPermissions($permissionIds);
        $this->copyLegacyMarketingPermissions($permissionIds);
    }

    public function down(): void
    {
        // Keep added permission records for backward compatibility.
    }

    private function copyLegacyFacebookPermissions($permissionIds): void
    {
        foreach (['facebook.view' => ['business_management.view', 'business_managers.view', 'ad_accounts.view', 'ad_account_ledger.view', 'page_management.view', 'pages.view', 'campaigns.view'], 'facebook.manage' => ['business_management.manage', 'business_managers.manage', 'ad_accounts.manage', 'page_management.manage', 'pages.manage', 'campaigns.manage']] as $legacy => $modernKeys) {
            if (! isset($permissionIds[$legacy])) {
                continue;
            }

            $roleIds = DB::table('role_permissions')->where('permission_id', $permissionIds[$legacy])->pluck('role_id');
            foreach ($roleIds as $roleId) {
                foreach ($modernKeys as $modern) {
                    if (isset($permissionIds[$modern])) {
                        DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionIds[$modern]]);
                    }
                }
            }
        }
    }

    private function copyLegacyMarketingPermissions($permissionIds): void
    {
        $map = [
            'marketing_operations.view' => ['agency_operations.view'],
            'marketing_operations.manage' => ['agency_operations.manage', 'agency_operations.verify', 'agency_operations.approve'],
            'marketing_operations.verify' => ['agency_operations.verify'],
            'marketing_operations.approve' => ['agency_operations.approve'],
            'marketing_operations.submit' => ['moderator_operations.submit', 'ad_manager_operations.submit', 'auditor_operations.submit', 'monitor_operations.submit'],
        ];

        foreach ($map as $legacy => $modernKeys) {
            if (! isset($permissionIds[$legacy])) {
                continue;
            }

            $roleIds = DB::table('role_permissions')->where('permission_id', $permissionIds[$legacy])->pluck('role_id');
            foreach ($roleIds as $roleId) {
                foreach ($modernKeys as $modern) {
                    if (isset($permissionIds[$modern])) {
                        DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionIds[$modern]]);
                    }
                }
            }
        }
    }
};
