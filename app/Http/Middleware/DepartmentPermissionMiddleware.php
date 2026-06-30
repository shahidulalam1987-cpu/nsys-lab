<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentPermissionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $permissions = $this->requiredPermissions($request);

        if ($permissions && $user->hasAnyPermission($permissions)) {
            return $next($request);
        }

        abort(403, 'Your role cannot access this department.');
    }

    private function requiredPermissions(Request $request): array
    {
        $manage = ! $request->isMethod('GET');

        return match (true) {
            $request->is('admin/dashboard') => ['dashboard.view'],

            $request->is('admin/notifications*') => ['system_tools.view'],

            $request->is('admin/bug-tracker*', 'admin/activity-log*', 'admin/security-audit*', 'admin/test-data-reset*')
                => [$manage ? 'system_tools.manage' : 'system_tools.view'],

            $request->is('admin/client-fund*', 'admin/salary-payments*')
                => [$manage ? 'client_fund.manage' : 'client_fund.view'],

            $request->is('admin/financial-management', 'admin/finance*', 'admin/facebook-cards*', 'admin/facebook-financial*')
                => [$manage ? 'finance.manage' : 'finance.view'],

            $request->is('admin/payroll*', 'admin/salary-month-sheet*')
                => [$manage ? 'payroll.manage' : 'payroll.view'],

            $request->is('admin/attendance*')
                => [$manage ? 'attendance.manage' : 'attendance.view'],

            $request->is('admin/work-status*')
                => [$manage ? 'work_status.manage' : 'work_status.view'],

            $request->is('admin/assignments*', 'admin/employee-assignments*')
                => [$manage ? 'assignments.manage' : 'assignments.view'],

            $request->is('admin/employee-notices*')
                => [$manage ? 'notices.manage' : 'notices.view'],

            $request->is('admin/departments*')
                => [$manage ? 'departments.manage' : 'departments.view'],

            $request->is('admin/employee-roles*')
                => [$manage ? 'employee_roles.manage' : 'employee_roles.view'],

            $request->is('admin/client-pages*')
                => [$manage ? 'employees.manage' : 'employees.view', $manage ? 'facebook.manage' : 'facebook.view'],

            $request->is('admin/employee-dashboard', 'admin/employees*', 'admin/salary-days*')
                => [$manage ? 'employees.manage' : 'employees.view'],

            $request->is('admin/client-dashboard', 'admin/clients*', 'admin/client-users*', 'admin/invoices*')
                => [$manage ? 'clients.manage' : 'clients.view'],

            $request->is('admin/daily-reports*', 'admin/export/daily-reports')
                => [$manage ? 'daily_reports.manage' : 'daily_reports.view'],

            $request->is('admin/facebook-dashboard', 'admin/business-managers*', 'admin/ad-accounts*', 'admin/ad-account-ledger*', 'admin/campaigns*', 'admin/payments*', 'admin/profit-history*', 'admin/export/profit-history', 'admin/export/payments')
                => [$manage ? 'facebook.manage' : 'facebook.view'],

            $request->is('admin/tiktok*')
                => [$manage ? 'tiktok.manage' : 'tiktok.view'],

            default => [],
        };
    }
}
