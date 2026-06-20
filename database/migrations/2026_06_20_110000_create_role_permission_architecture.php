<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('module');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        $now = now();
        $roles = [
            'super_admin' => 'Super Admin',
            'finance_manager' => 'Finance Manager',
            'hr_manager' => 'HR Manager',
            'facebook_manager' => 'Facebook Manager',
            'tiktok_manager' => 'TikTok Manager',
            'moderator' => 'Moderator',
            'client' => 'Client',
            'employee' => 'Employee',
        ];
        foreach ($roles as $slug => $name) {
            DB::table('roles')->insert(['name' => $name, 'slug' => $slug, 'created_at' => $now, 'updated_at' => $now]);
        }

        $permissions = [
            'dashboard.view' => ['Dashboard View', 'dashboard'],
            'finance.view' => ['Finance View', 'finance'],
            'finance.manage' => ['Finance Manage', 'finance'],
            'client_fund.view' => ['Client Fund View', 'clients'],
            'client_fund.manage' => ['Client Fund Manage', 'clients'],
            'clients.view' => ['Clients View', 'clients'],
            'clients.manage' => ['Clients Manage', 'clients'],
            'employees.view' => ['Employees View', 'employees'],
            'employees.manage' => ['Employees Manage', 'employees'],
            'assignments.view' => ['Assignments View', 'employees'],
            'assignments.manage' => ['Assignments Manage', 'employees'],
            'attendance.view' => ['Attendance View', 'employees'],
            'attendance.manage' => ['Attendance Manage', 'employees'],
            'work_status.view' => ['Work Status View', 'employees'],
            'work_status.manage' => ['Work Status Manage', 'employees'],
            'payroll.view' => ['Payroll View', 'payroll'],
            'payroll.manage' => ['Payroll Manage', 'payroll'],
            'notices.view' => ['Notices View', 'employees'],
            'notices.manage' => ['Notices Manage', 'employees'],
            'facebook.view' => ['Facebook View', 'facebook'],
            'facebook.manage' => ['Facebook Manage', 'facebook'],
            'daily_reports.view' => ['Daily Reports View', 'facebook'],
            'daily_reports.manage' => ['Daily Reports Manage', 'facebook'],
            'tiktok.view' => ['TikTok View', 'tiktok'],
            'tiktok.manage' => ['TikTok Manage', 'tiktok'],
            'system_tools.view' => ['System Tools View', 'system_tools'],
            'system_tools.manage' => ['System Tools Manage', 'system_tools'],
            'reports.view' => ['Reports View', 'reports'],
            'reports.export' => ['Reports Export', 'reports'],
            'own_profile.view' => ['Own Profile View', 'portal'],
            'own_attendance.manage' => ['Own Attendance Manage', 'portal'],
            'own_salary.view' => ['Own Salary View', 'portal'],
            'own_documents.view' => ['Own Documents View', 'portal'],
            'own_notices.view' => ['Own Notices View', 'portal'],
            'own_client_data.view' => ['Own Client Data View', 'portal'],
        ];
        foreach ($permissions as $key => [$name, $module]) {
            DB::table('permissions')->insert(compact('name', 'key', 'module') + ['created_at' => $now, 'updated_at' => $now]);
        }

        $grants = [
            'finance_manager' => ['dashboard.view', 'finance.view', 'finance.manage', 'client_fund.view', 'client_fund.manage', 'reports.view', 'reports.export'],
            'hr_manager' => ['dashboard.view', 'employees.view', 'employees.manage', 'assignments.view', 'assignments.manage', 'attendance.view', 'attendance.manage', 'work_status.view', 'work_status.manage', 'payroll.view', 'payroll.manage', 'notices.view', 'notices.manage', 'reports.view', 'reports.export'],
            'facebook_manager' => ['dashboard.view', 'facebook.view', 'facebook.manage', 'daily_reports.view', 'daily_reports.manage', 'reports.view', 'reports.export'],
            'tiktok_manager' => ['dashboard.view', 'tiktok.view', 'tiktok.manage', 'reports.view'],
            'moderator' => ['dashboard.view', 'work_status.view', 'work_status.manage', 'daily_reports.view', 'daily_reports.manage', 'own_notices.view'],
            'client' => ['own_client_data.view'],
            'employee' => ['own_profile.view', 'own_attendance.manage', 'own_salary.view', 'own_documents.view', 'own_notices.view'],
        ];

        $permissionIds = DB::table('permissions')->pluck('id', 'key');
        $roleIds = DB::table('roles')->pluck('id', 'slug');
        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert(['role_id' => $roleIds['super_admin'], 'permission_id' => $permissionId]);
        }
        foreach ($grants as $role => $keys) {
            foreach ($keys as $key) {
                DB::table('role_permissions')->insert(['role_id' => $roleIds[$role], 'permission_id' => $permissionIds[$key]]);
            }
        }

        DB::table('users')->orderBy('id')->each(function ($user) use ($roleIds) {
            $role = match ($user->role) {
                'admin' => 'super_admin',
                'client' => 'client',
                'employee' => 'employee',
                default => null,
            };
            if ($role) {
                DB::table('user_roles')->insertOrIgnore(['user_id' => $user->id, 'role_id' => $roleIds[$role]]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
