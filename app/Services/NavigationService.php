<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class NavigationService
{
    private ?array $navigation = null;

    public function __construct(private NavigationBadgeService $badges)
    {
    }

    public function forRequest(Request $request): array
    {
        return $this->navigation ??= $this->build($request);
    }

    public function breadcrumbs(Request $request): array
    {
        return $this->forRequest($request)['breadcrumbs'];
    }

    private function build(Request $request): array
    {
        $user = $request->user();
        $sections = collect($this->registry())
            ->map(fn (array $section) => $this->prepareSection($section, $request, $user))
            ->filter(fn (array $section) => ! empty($section['items']))
            ->values()
            ->all();

        $activeSection = collect($sections)->firstWhere('active', true) ?: ($sections[0] ?? null);
        $activeItem = $activeSection
            ? collect($activeSection['items'])->firstWhere('active', true)
            : null;

        return [
            'sections' => $sections,
            'active_section' => $activeSection,
            'active_item' => $activeItem,
            'breadcrumbs' => array_values(array_filter([
                $activeSection ? ['label' => $activeSection['label'], 'url' => $activeSection['url']] : null,
                $activeItem ? ['label' => $activeItem['label'], 'url' => $activeItem['url']] : null,
            ])),
        ];
    }

    private function prepareSection(array $section, Request $request, ?User $user): array
    {
        $items = collect($section['items'])
            ->filter(fn (array $item) => $this->canAccess($item, $user))
            ->map(fn (array $item) => $this->prepareItem($item, $request))
            ->values()
            ->all();

        $active = collect($items)->contains(fn (array $item) => $item['active']);
        $firstUrl = $items[0]['url'] ?? $section['url'];
        $badge = $this->badgeCount($section['badge'] ?? null);

        return [
            'key' => $section['key'],
            'label' => $section['label'],
            'url' => $firstUrl,
            'icon' => $section['icon'] ?? null,
            'badge_key' => $section['badge'] ?? null,
            'badge' => $badge,
            'active' => $active,
            'items' => $items,
        ];
    }

    private function prepareItem(array $item, Request $request): array
    {
        $active = ($item['key'] ?? null) === 'payroll_dashboard'
            ? $request->is('admin/payroll') && ! $request->filled('status') && ! $request->filled('employee_scope')
            : $this->matches($request, $item['active'] ?? [$item['url']]);
        $badge = $this->badgeCount($item['badge'] ?? null);

        return [
            'key' => $item['key'],
            'label' => $item['label'],
            'url' => $item['url'],
            'icon' => $item['icon'] ?? null,
            'badge_key' => $item['badge'] ?? null,
            'badge' => $badge,
            'badge_danger' => $item['badge_danger'] ?? false,
            'active' => $active,
        ];
    }

    private function canAccess(array $item, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = $item['permissions'] ?? [];

        if (in_array('super_admin', $permissions, true)) {
            return false;
        }

        return empty($permissions) || $user->hasAnyPermission($permissions);
    }

    private function matches(Request $request, array|string $patterns): bool
    {
        foreach ((array) $patterns as $pattern) {
            if (str_contains($pattern, '?')) {
                [$path, $query] = explode('?', $pattern, 2);
                parse_str($query, $params);
                if (! $request->is(ltrim($path, '/'))) {
                    continue;
                }
                foreach ($params as $key => $value) {
                    if ((string) $request->query($key) !== (string) $value) {
                        continue 2;
                    }
                }
                return true;
            }

            if ($request->is(ltrim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    private function badgeCount(?string $key): int
    {
        return $key ? $this->badges->count($key) : 0;
    }

    private function registry(): array
    {
        return [
            [
                'key' => 'admin_dashboard',
                'label' => 'Admin Dashboard',
                'url' => '/admin/dashboard',
                'icon' => 'layout-dashboard',
                'items' => [
                    ['key' => 'overview', 'label' => 'Overview', 'url' => '/admin/dashboard', 'active' => ['/admin/dashboard'], 'permissions' => ['dashboard.view']],
                    ['key' => 'executive', 'label' => 'Executive Dashboard', 'url' => '/admin/executive-performance', 'active' => ['/admin/executive-performance*'], 'permissions' => ['dashboard.view']],
                    ['key' => 'notifications', 'label' => 'Notification Center', 'url' => '/admin/notifications?status=unread', 'active' => ['/admin/notifications*'], 'permissions' => ['system_tools.view'], 'badge' => 'notifications_unread', 'badge_danger' => true],
                    ['key' => 'recent_activity', 'label' => 'Recent Activity', 'url' => '/admin/activity-log', 'active' => ['/admin/activity-log*'], 'permissions' => ['system_tools.view']],
                ],
            ],
            [
                'key' => 'agency_operations',
                'label' => 'Agency Operations',
                'url' => '/admin/marketing-operations',
                'icon' => 'workflow',
                'items' => [
                    ['key' => 'operations_dashboard', 'label' => 'Operations Dashboard', 'url' => '/admin/marketing-operations', 'active' => ['/admin/marketing-operations'], 'permissions' => ['marketing_operations.view', 'marketing_operations.submit', 'facebook.view', 'daily_reports.view']],
                    ['key' => 'moderator_operations', 'label' => 'Moderator Operations', 'url' => '/admin/marketing-operations/moderator/operations', 'active' => ['/admin/marketing-operations/moderator/operations*'], 'permissions' => ['marketing_operations.view', 'marketing_operations.submit']],
                    ['key' => 'ad_manager_operations', 'label' => 'Ad Manager Operations', 'url' => '/admin/marketing-operations/ad-manager/operations', 'active' => ['/admin/marketing-operations/ad-manager/operations*'], 'permissions' => ['marketing_operations.view', 'marketing_operations.submit']],
                    ['key' => 'auditor_operations', 'label' => 'Auditor Operations', 'url' => '/admin/marketing-operations/auditor/operations', 'active' => ['/admin/marketing-operations/auditor/operations*'], 'permissions' => ['marketing_operations.view', 'marketing_operations.verify']],
                    ['key' => 'monitor_operations', 'label' => 'Monitor Operations', 'url' => '/admin/marketing-operations/monitor/operations', 'active' => ['/admin/marketing-operations/monitor/operations*'], 'permissions' => ['marketing_operations.view', 'marketing_operations.verify']],
                    ['key' => 'agency_review', 'label' => 'Agency Review', 'url' => '/admin/marketing-operations/agency', 'active' => ['/admin/marketing-operations/agency'], 'permissions' => ['marketing_operations.agency', 'marketing_operations.manage']],
                    ['key' => 'performance_verification', 'label' => 'Performance Verification', 'url' => '/admin/performance-verification', 'active' => ['/admin/performance-verification*'], 'permissions' => ['performance.view']],
                    ['key' => 'employee_submissions', 'label' => 'Employee Submissions', 'url' => '/admin/employee-submissions', 'active' => ['/admin/employee-submissions*'], 'permissions' => ['performance.view'], 'badge' => 'pending_employee_submissions', 'badge_danger' => true],
                    ['key' => 'daily_performance', 'label' => 'Daily Performance', 'url' => '/admin/daily-reports', 'active' => ['/admin/daily-reports*'], 'permissions' => ['daily_reports.view']],
                    ['key' => 'operations_reports', 'label' => 'Operations Reports', 'url' => '/admin/marketing-operations/reports', 'active' => ['/admin/marketing-operations/reports'], 'permissions' => ['marketing_operations.view']],
                    ['key' => 'operations_settings', 'label' => 'Operations Settings', 'url' => '/admin/marketing-operations/settings', 'active' => ['/admin/marketing-operations/settings'], 'permissions' => ['marketing_operations.manage']],
                ],
            ],
            [
                'key' => 'clients',
                'label' => 'Clients',
                'url' => '/admin/client-dashboard',
                'icon' => 'users',
                'items' => [
                    ['key' => 'client_dashboard', 'label' => 'Client Dashboard', 'url' => '/admin/client-dashboard', 'active' => ['/admin/client-dashboard'], 'permissions' => ['clients.view']],
                    ['key' => 'client_list', 'label' => 'Client List', 'url' => '/admin/clients', 'active' => ['/admin/clients', '/admin/clients/create', '/admin/clients/*'], 'permissions' => ['clients.view']],
                    ['key' => 'client_users', 'label' => 'Client Users', 'url' => '/admin/client-users', 'active' => ['/admin/client-users*'], 'permissions' => ['clients.view']],
                    ['key' => 'receive_client_payment', 'label' => 'Receive Client Payment', 'url' => '/admin/salary-payments/create', 'active' => ['/admin/salary-payments/create'], 'permissions' => ['client_fund.view']],
                    ['key' => 'pending_payments', 'label' => 'Pending Payments', 'url' => '/admin/salary-payments/pending', 'active' => ['/admin/salary-payments/pending'], 'permissions' => ['client_fund.view'], 'badge' => 'pending_client_payments', 'badge_danger' => true],
                    ['key' => 'payment_history', 'label' => 'Payment History', 'url' => '/admin/salary-payments', 'active' => ['/admin/salary-payments'], 'permissions' => ['client_fund.view']],
                    ['key' => 'client_funds', 'label' => 'Client Funds', 'url' => '/admin/client-fund', 'active' => ['/admin/client-fund*'], 'permissions' => ['client_fund.view']],
                ],
            ],
            [
                'key' => 'employees',
                'label' => 'Employees',
                'url' => '/admin/employee-dashboard',
                'icon' => 'briefcase-business',
                'items' => [
                    ['key' => 'employee_dashboard', 'label' => 'Employee Dashboard', 'url' => '/admin/employee-dashboard', 'active' => ['/admin/employee-dashboard'], 'permissions' => ['employees.view']],
                    ['key' => 'employee_list', 'label' => 'Employee List', 'url' => '/admin/employees', 'active' => ['/admin/employees*'], 'permissions' => ['employees.view']],
                    ['key' => 'departments', 'label' => 'Departments', 'url' => '/admin/departments', 'active' => ['/admin/departments*'], 'permissions' => ['departments.view']],
                    ['key' => 'roles', 'label' => 'Roles', 'url' => '/admin/employee-roles', 'active' => ['/admin/employee-roles*'], 'permissions' => ['employee_roles.view']],
                    ['key' => 'assignments', 'label' => 'Assignments', 'url' => '/admin/assignments', 'active' => ['/admin/assignments*'], 'permissions' => ['assignments.view']],
                    ['key' => 'work_status', 'label' => 'Work Status', 'url' => '/admin/work-status', 'active' => ['/admin/work-status*'], 'permissions' => ['work_status.view']],
                    ['key' => 'attendance', 'label' => 'Attendance', 'url' => '/admin/attendance', 'active' => ['/admin/attendance*'], 'permissions' => ['attendance.view']],
                    ['key' => 'notice_board', 'label' => 'Notice Board', 'url' => '/admin/employee-notices', 'active' => ['/admin/employee-notices*'], 'permissions' => ['notices.view']],
                    ['key' => 'payroll_dashboard', 'label' => 'Payroll Dashboard', 'url' => '/admin/payroll', 'active' => ['/admin/payroll'], 'permissions' => ['payroll.view']],
                    ['key' => 'upcoming_salary', 'label' => 'Upcoming Salary', 'url' => '/admin/payroll?status=upcoming', 'active' => ['/admin/payroll?status=upcoming'], 'permissions' => ['payroll.view'], 'badge' => 'upcoming_salary'],
                    ['key' => 'unpaid_salary', 'label' => 'Unpaid Salary', 'url' => '/admin/payroll?status=due', 'active' => ['/admin/payroll?status=due'], 'permissions' => ['payroll.view'], 'badge' => 'unpaid_salary', 'badge_danger' => true],
                    ['key' => 'salary_report', 'label' => 'Salary Report', 'url' => '/admin/salary-month-sheet', 'active' => ['/admin/salary-month-sheet*'], 'permissions' => ['payroll.view']],
                    ['key' => 'final_settlement', 'label' => 'Final Settlement', 'url' => '/admin/payroll?status=due&employee_scope=terminated', 'active' => ['/admin/payroll?status=due&employee_scope=terminated'], 'permissions' => ['payroll.view']],
                    ['key' => 'performance_dashboard', 'label' => 'Performance Dashboard', 'url' => '/admin/employee-kpi', 'active' => ['/admin/employee-kpi*'], 'permissions' => ['kpi.view']],
                    ['key' => 'leaderboard', 'label' => 'Leaderboard', 'url' => '/admin/leaderboard', 'active' => ['/admin/leaderboard*'], 'permissions' => ['leaderboard.view']],
                    ['key' => 'performance_targets', 'label' => 'Performance Targets', 'url' => '/admin/performance-targets', 'active' => ['/admin/performance-targets*'], 'permissions' => ['targets.manage']],
                    ['key' => 'bonus_review', 'label' => 'Bonus Review', 'url' => '/admin/bonuses', 'active' => ['/admin/bonuses*'], 'permissions' => ['bonus.view']],
                ],
            ],
            [
                'key' => 'business_management',
                'label' => 'Business Management',
                'url' => '/admin/business-managers',
                'icon' => 'building-2',
                'items' => [
                    ['key' => 'business_managers', 'label' => 'Business Managers', 'url' => '/admin/business-managers', 'active' => ['/admin/business-managers*'], 'permissions' => ['facebook.view']],
                    ['key' => 'ad_accounts', 'label' => 'Ad Accounts', 'url' => '/admin/ad-accounts', 'active' => ['/admin/ad-accounts*'], 'permissions' => ['facebook.view'], 'badge' => 'ad_account_billing', 'badge_danger' => true],
                    ['key' => 'ad_account_ledger', 'label' => 'Ad Account Ledger', 'url' => '/admin/ad-account-ledger', 'active' => ['/admin/ad-account-ledger*'], 'permissions' => ['facebook.view']],
                ],
            ],
            [
                'key' => 'page_management',
                'label' => 'Page Management',
                'url' => '/admin/client-pages',
                'icon' => 'panels-top-left',
                'items' => [
                    ['key' => 'pages', 'label' => 'Pages', 'url' => '/admin/client-pages', 'active' => ['/admin/client-pages*'], 'permissions' => ['facebook.view', 'employees.view']],
                    ['key' => 'campaigns', 'label' => 'Campaigns', 'url' => '/admin/campaigns', 'active' => ['/admin/campaigns*'], 'permissions' => ['facebook.view']],
                    ['key' => 'daily_performance', 'label' => 'Daily Performance', 'url' => '/admin/daily-reports', 'active' => ['/admin/daily-reports*'], 'permissions' => ['daily_reports.view']],
                    ['key' => 'page_performance', 'label' => 'Page Performance', 'url' => '/admin/profit-history', 'active' => ['/admin/profit-history*'], 'permissions' => ['facebook.view']],
                ],
            ],
            [
                'key' => 'finance',
                'label' => 'Finance',
                'url' => '/admin/financial-management',
                'icon' => 'wallet',
                'items' => [
                    ['key' => 'finance_dashboard', 'label' => 'Finance Dashboard', 'url' => '/admin/financial-management', 'active' => ['/admin/financial-management*'], 'permissions' => ['finance.view']],
                    ['key' => 'funding_dashboard', 'label' => 'Funding Dashboard', 'url' => '/admin/facebook-financial/funding-dashboard', 'active' => ['/admin/facebook-financial/funding-dashboard*'], 'permissions' => ['finance.view'], 'badge' => 'low_funding_balance', 'badge_danger' => true],
                    ['key' => 'finance_accounts', 'label' => 'Finance Accounts', 'url' => '/admin/finance/accounts', 'active' => ['/admin/finance/accounts*'], 'permissions' => ['finance.view']],
                    ['key' => 'client_funds', 'label' => 'Client Funds', 'url' => '/admin/client-fund', 'active' => ['/admin/client-fund*'], 'permissions' => ['client_fund.view']],
                    ['key' => 'card_management', 'label' => 'Card Management', 'url' => '/admin/facebook-cards', 'active' => ['/admin/facebook-cards*', '/admin/facebook-financial/card-loads*', '/admin/facebook-financial/card-transactions*', '/admin/facebook-financial/binance-purchases*'], 'permissions' => ['finance.view'], 'badge' => 'low_card_balance', 'badge_danger' => true],
                    ['key' => 'family_expenses', 'label' => 'Family Expenses', 'url' => '/admin/finance/family-expenses', 'active' => ['/admin/finance/family-expenses*'], 'permissions' => ['finance.view']],
                    ['key' => 'loan_management', 'label' => 'Loan Management', 'url' => '/admin/finance/loans', 'active' => ['/admin/finance/loans*'], 'permissions' => ['finance.view']],
                    ['key' => 'reconciliation', 'label' => 'Reconciliation', 'url' => '/admin/finance/reports/reconciliation', 'active' => ['/admin/finance/reports/reconciliation*'], 'permissions' => ['finance.view']],
                    ['key' => 'finance_reports', 'label' => 'Finance Reports', 'url' => '/admin/finance/reports/balance-sheet', 'active' => ['/admin/finance/reports*'], 'permissions' => ['finance.view']],
                ],
            ],
            [
                'key' => 'system_tools',
                'label' => 'System Tools',
                'url' => '/admin/automation',
                'icon' => 'settings',
                'items' => [
                    ['key' => 'automation', 'label' => 'Automation', 'url' => '/admin/automation', 'active' => ['/admin/automation*'], 'permissions' => ['system_tools.view'], 'badge' => 'automation_pending'],
                    ['key' => 'documents', 'label' => 'Document Management', 'url' => '/admin/documents', 'active' => ['/admin/documents*'], 'permissions' => ['documents.view', 'system_tools.view']],
                    ['key' => 'bug_tracker', 'label' => 'Bug Tracker', 'url' => '/admin/bug-tracker', 'active' => ['/admin/bug-tracker*'], 'permissions' => ['system_tools.view'], 'badge' => 'open_bugs', 'badge_danger' => true],
                    ['key' => 'activity_log', 'label' => 'Activity Log', 'url' => '/admin/activity-log', 'active' => ['/admin/activity-log*'], 'permissions' => ['system_tools.view']],
                    ['key' => 'security_audit', 'label' => 'Security Audit', 'url' => '/admin/security-audit', 'active' => ['/admin/security-audit*'], 'permissions' => ['system_tools.view']],
                    ['key' => 'test_data_reset', 'label' => 'Test Data Reset', 'url' => '/admin/test-data-reset', 'active' => ['/admin/test-data-reset*'], 'permissions' => ['super_admin']],
                ],
            ],
        ];
    }
}
