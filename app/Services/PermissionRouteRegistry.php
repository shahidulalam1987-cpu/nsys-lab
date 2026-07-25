<?php

namespace App\Services;

use Illuminate\Http\Request;

class PermissionRouteRegistry
{
    public const SUPER_ADMIN_ONLY = '__super_admin_only__';

    public function permissionsFor(Request $request): array
    {
        foreach ($this->routeRules() as $rule) {
            if ($this->matches($request, $rule['patterns'])) {
                if (isset($rule['permissions'])) {
                    return (array) $rule['permissions'];
                }

                $mode = $this->mode($request, $rule);

                return (array) ($rule[$mode] ?? $rule['view'] ?? []);
            }
        }

        return [];
    }

    public function navigationSections(): array
    {
        return [
            [
                'key' => 'admin_dashboard',
                'label' => 'Admin Dashboard',
                'url' => '/admin/dashboard',
                'icon' => 'layout-dashboard',
                'items' => [
                    ['key' => 'overview', 'label' => 'Overview', 'url' => '/admin/dashboard', 'active' => ['/admin/dashboard'], 'permissions' => ['admin_dashboard.view']],
                    ['key' => 'executive', 'label' => 'Executive Dashboard', 'url' => '/admin/executive-performance', 'active' => ['/admin/executive-performance*'], 'permissions' => ['admin_dashboard.view']],
                    ['key' => 'notifications', 'label' => 'Notification Center', 'url' => '/admin/notifications?status=unread', 'active' => ['/admin/notifications*'], 'permissions' => ['system_tools.view'], 'badge' => 'notifications_unread', 'badge_danger' => true],
                ],
            ],
            [
                'key' => 'agency_operations',
                'label' => 'Agency Operations',
                'url' => '/admin/marketing-operations',
                'icon' => 'workflow',
                'items' => [
                    ['key' => 'operations_dashboard', 'label' => 'Operations Dashboard', 'url' => '/admin/marketing-operations', 'active' => ['/admin/marketing-operations'], 'permissions' => ['agency_operations.view']],
                    ['key' => 'auditor_operations', 'label' => 'Auditor Operations', 'url' => '/admin/marketing-operations/auditor/operations', 'active' => ['/admin/marketing-operations/auditor/operations*'], 'permissions' => ['auditor_operations.view']],
                    ['key' => 'monitor_operations', 'label' => 'Monitor Operations', 'url' => '/admin/marketing-operations/monitor/operations', 'active' => ['/admin/marketing-operations/monitor/operations*'], 'permissions' => ['monitor_operations.view']],
                    ['key' => 'agency_review', 'label' => 'Agency Review', 'url' => '/admin/marketing-operations/agency', 'active' => ['/admin/marketing-operations/agency'], 'permissions' => ['agency_operations.approve']],
                    ['key' => 'daily_performance', 'label' => 'Daily Performance', 'url' => '/admin/daily-reports', 'active' => ['/admin/daily-reports*'], 'permissions' => ['daily_performance.view']],
                    ['key' => 'operations_reports', 'label' => 'Operations Reports', 'url' => '/admin/marketing-operations/reports', 'active' => ['/admin/marketing-operations/reports'], 'permissions' => ['agency_operations.view']],
                    ['key' => 'operations_settings', 'label' => 'Operations Settings', 'url' => '/admin/marketing-operations/settings', 'active' => ['/admin/marketing-operations/settings'], 'permissions' => ['agency_operations.manage']],
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
                    ['key' => 'client_funds', 'label' => 'Client Funds', 'url' => '/admin/client-fund', 'active' => ['/admin/client-fund', '/admin/client-fund/*/details*'], 'permissions' => ['client_funds.view']],
                    ['key' => 'daily_statement', 'label' => 'Daily Statement', 'url' => '/admin/client-fund/daily-statement', 'active' => ['/admin/client-fund/daily-statement*'], 'permissions' => ['client_funds.view']],
                    ['key' => 'receive_client_payment', 'label' => 'Receive Client Payment', 'url' => '/admin/salary-payments/create', 'active' => ['/admin/salary-payments/create'], 'permissions' => ['client_payments.view']],
                    ['key' => 'pending_payments', 'label' => 'Pending Payments', 'url' => '/admin/salary-payments/pending', 'active' => ['/admin/salary-payments/pending'], 'permissions' => ['client_payments.view'], 'badge' => 'pending_client_payments', 'badge_danger' => true],
                    ['key' => 'payment_history', 'label' => 'Payment History', 'url' => '/admin/salary-payments', 'active' => ['/admin/salary-payments'], 'permissions' => ['client_payments.view']],
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
                    ['key' => 'departments', 'label' => 'Departments', 'url' => '/admin/departments', 'active' => ['/admin/departments*'], 'permissions' => ['departments.manage']],
                    ['key' => 'roles', 'label' => 'Roles', 'url' => '/admin/employee-roles', 'active' => ['/admin/employee-roles*'], 'permissions' => ['roles.manage']],
                    ['key' => 'assignments', 'label' => 'Assignments', 'url' => '/admin/assignments', 'active' => ['/admin/assignments*'], 'permissions' => ['assignments.view']],
                    ['key' => 'attendance', 'label' => 'Attendance', 'url' => '/admin/attendance', 'active' => ['/admin/attendance*'], 'permissions' => ['attendance.view']],
                    ['key' => 'work_status', 'label' => 'Work Status', 'url' => '/admin/work-status', 'active' => ['/admin/work-status*'], 'permissions' => ['work_status.view']],
                    ['key' => 'notice_board', 'label' => 'Notice Board', 'url' => '/admin/employee-notices', 'active' => ['/admin/employee-notices*'], 'permissions' => ['notices.view']],
                    ['key' => 'performance_dashboard', 'label' => 'Employee Performance', 'url' => '/admin/employee-kpi', 'active' => ['/admin/employee-kpi*'], 'permissions' => ['kpi.view']],
                    ['key' => 'leaderboard', 'label' => 'Leaderboard', 'url' => '/admin/leaderboard', 'active' => ['/admin/leaderboard*'], 'permissions' => ['leaderboard.view']],
                    ['key' => 'performance_targets', 'label' => 'Performance Targets', 'url' => '/admin/performance-targets', 'active' => ['/admin/performance-targets*'], 'permissions' => ['targets.manage']],
                    ['key' => 'bonus_review', 'label' => 'Bonus Review', 'url' => '/admin/bonuses', 'active' => ['/admin/bonuses*'], 'permissions' => ['bonus.view']],
                    ['key' => 'payroll_dashboard', 'label' => 'Payroll Dashboard', 'url' => '/admin/payroll', 'active' => ['/admin/payroll'], 'permissions' => ['payroll.view']],
                    ['key' => 'upcoming_salary', 'label' => 'Upcoming Salary', 'url' => '/admin/payroll?status=upcoming', 'active' => ['/admin/payroll?status=upcoming'], 'permissions' => ['payroll.view'], 'badge' => 'upcoming_salary'],
                    ['key' => 'unpaid_salary', 'label' => 'Payroll Action Queue', 'url' => '/admin/payroll?status=due', 'active' => ['/admin/payroll?status=due'], 'permissions' => ['payroll.view'], 'badge' => 'unpaid_salary', 'badge_danger' => true],
                    ['key' => 'final_settlement', 'label' => 'Final Settlement', 'url' => '/admin/payroll?status=due&employee_scope=terminated', 'active' => ['/admin/payroll?status=due&employee_scope=terminated'], 'permissions' => ['final_settlement.view']],
                    ['key' => 'salary_report', 'label' => 'Salary Report', 'url' => '/admin/salary-month-sheet', 'active' => ['/admin/salary-month-sheet*'], 'permissions' => ['payroll.view']],
                ],
            ],
            [
                'key' => 'business_management',
                'label' => 'Business Management',
                'url' => '/admin/business-managers',
                'icon' => 'building-2',
                'items' => [
                    ['key' => 'business_managers', 'label' => 'Business Managers', 'url' => '/admin/business-managers', 'active' => ['/admin/business-managers*'], 'permissions' => ['business_managers.view']],
                    ['key' => 'ad_accounts', 'label' => 'Ad Accounts', 'url' => '/admin/ad-accounts', 'active' => ['/admin/ad-accounts*'], 'permissions' => ['ad_accounts.view'], 'badge' => 'ad_account_billing', 'badge_danger' => true],
                    ['key' => 'datasets', 'label' => 'Pixels & Datasets', 'url' => '/admin/datasets', 'active' => ['/admin/datasets*'], 'permissions' => ['campaigns.view']],
                    ['key' => 'pages', 'label' => 'Pages', 'url' => '/admin/client-pages', 'active' => ['/admin/client-pages*'], 'permissions' => ['pages.view']],
                    ['key' => 'campaigns', 'label' => 'Campaigns', 'url' => '/admin/campaigns', 'active' => ['/admin/campaigns*'], 'permissions' => ['campaigns.view']],
                    ['key' => 'ad_account_ledger', 'label' => 'Ad Account Ledger', 'url' => '/admin/ad-account-ledger', 'active' => ['/admin/ad-account-ledger*'], 'permissions' => ['ad_account_ledger.view']],
                    ['key' => 'billing_history', 'label' => 'Billing History', 'url' => '/admin/ad-account-billing-history', 'active' => ['/admin/ad-account-billing-history*'], 'permissions' => ['ad_accounts.view']],
                    ['key' => 'performance_reports', 'label' => 'Performance Reports', 'url' => '/admin/profit-history', 'active' => ['/admin/profit-history*'], 'permissions' => ['daily_performance.view']],
                ],
            ],
            [
                'key' => 'finance',
                'label' => 'Finance',
                'url' => '/admin/financial-management',
                'icon' => 'wallet',
                'items' => [
                    ['key' => 'finance_dashboard', 'label' => 'Finance Dashboard', 'url' => '/admin/financial-management', 'active' => ['/admin/financial-management*'], 'permissions' => ['finance.view']],
                    ['key' => 'finance_accounts', 'label' => 'Finance Accounts', 'url' => '/admin/finance/accounts', 'active' => ['/admin/finance/accounts*'], 'permissions' => ['finance_accounts.view']],
                    ['key' => 'funding_dashboard', 'label' => 'Funding Dashboard', 'url' => '/admin/facebook-financial/funding-dashboard', 'active' => ['/admin/facebook-financial/funding-dashboard*'], 'permissions' => ['finance.view'], 'badge' => 'low_funding_balance', 'badge_danger' => true],
                    ['key' => 'card_management', 'label' => 'Card Management', 'url' => '/admin/facebook-cards', 'active' => ['/admin/facebook-cards*', '/admin/facebook-financial/card-loads*', '/admin/facebook-financial/card-transactions*', '/admin/facebook-financial/binance-purchases*', '/admin/payment-providers*', '/admin/provider-transactions*', '/admin/provider-fees*'], 'permissions' => ['cards.view'], 'badge' => 'low_card_balance', 'badge_danger' => true],
                    ['key' => 'reconciliation', 'label' => 'Reconciliation', 'url' => '/admin/finance/reports/reconciliation', 'active' => ['/admin/finance/reports/reconciliation*'], 'permissions' => ['reconciliation.view']],
                    ['key' => 'finance_reports', 'label' => 'Finance Reports', 'url' => '/admin/finance/reports/balance-sheet', 'active' => ['/admin/finance/reports/balance-sheet*'], 'permissions' => ['finance.view']],
                ],
            ],
            [
                'key' => 'system_tools',
                'label' => 'System Tools',
                'url' => '/admin/automation',
                'icon' => 'settings',
                'items' => [
                    ['key' => 'automation', 'label' => 'Automation', 'url' => '/admin/automation', 'active' => ['/admin/automation*'], 'permissions' => ['automation.view'], 'badge' => 'automation_pending'],
                    ['key' => 'bug_tracker', 'label' => 'Bug Tracker', 'url' => '/admin/bug-tracker', 'active' => ['/admin/bug-tracker*'], 'permissions' => ['system_tools.view'], 'badge' => 'open_bugs', 'badge_danger' => true],
                    ['key' => 'documents', 'label' => 'Document Management', 'url' => '/admin/documents', 'active' => ['/admin/documents*'], 'permissions' => ['documents.view']],
                    ['key' => 'activity_log', 'label' => 'Activity Log', 'url' => '/admin/activity-log', 'active' => ['/admin/activity-log*'], 'permissions' => ['activity_log.view']],
                    ['key' => 'security_audit', 'label' => 'Security Audit', 'url' => '/admin/security-audit', 'active' => ['/admin/security-audit*'], 'permissions' => ['security_audit.view']],
                    ['key' => 'test_data_reset', 'label' => 'Test Data Reset', 'url' => '/admin/test-data-reset', 'active' => ['/admin/test-data-reset*'], 'permissions' => [self::SUPER_ADMIN_ONLY]],
                ],
            ],
        ];
    }

    public function permissionCatalog(): array
    {
        return [
            'admin_dashboard.view' => ['Admin Dashboard View', 'dashboard'],
            'agency_operations.view' => ['Agency Operations View', 'agency_operations'],
            'agency_operations.manage' => ['Agency Operations Manage', 'agency_operations'],
            'agency_operations.verify' => ['Agency Operations Verify', 'agency_operations'],
            'agency_operations.approve' => ['Agency Operations Approve', 'agency_operations'],
            'moderator_operations.view' => ['Moderator Operations View', 'agency_operations'],
            'moderator_operations.submit' => ['Moderator Operations Submit', 'agency_operations'],
            'moderator_operations.manage' => ['Moderator Operations Manage', 'agency_operations'],
            'ad_manager_operations.view' => ['Ad Manager Operations View', 'agency_operations'],
            'ad_manager_operations.submit' => ['Ad Manager Operations Submit', 'agency_operations'],
            'ad_manager_operations.manage' => ['Ad Manager Operations Manage', 'agency_operations'],
            'auditor_operations.view' => ['Auditor Operations View', 'agency_operations'],
            'auditor_operations.submit' => ['Auditor Operations Submit', 'agency_operations'],
            'auditor_operations.manage' => ['Auditor Operations Manage', 'agency_operations'],
            'monitor_operations.view' => ['Monitor Operations View', 'agency_operations'],
            'monitor_operations.submit' => ['Monitor Operations Submit', 'agency_operations'],
            'monitor_operations.manage' => ['Monitor Operations Manage', 'agency_operations'],
            'trainer_operations.view' => ['Trainer Operations View', 'agency_operations'],
            'trainer_operations.submit' => ['Trainer Operations Submit', 'agency_operations'],
            'trainer_operations.manage' => ['Trainer Operations Manage', 'agency_operations'],
            'clients.view' => ['Clients View', 'clients'],
            'clients.manage' => ['Clients Manage', 'clients'],
            'client_payments.view' => ['Client Payments View', 'clients'],
            'client_payments.manage' => ['Client Payments Manage', 'clients'],
            'client_funds.view' => ['Client Funds View', 'clients'],
            'client_funds.manage' => ['Client Funds Manage', 'clients'],
            'employees.view' => ['Employees View', 'employees'],
            'employees.manage' => ['Employees Manage', 'employees'],
            'departments.manage' => ['Departments Manage', 'employees'],
            'roles.manage' => ['Roles Manage', 'employees'],
            'assignments.view' => ['Assignments View', 'employees'],
            'assignments.manage' => ['Assignments Manage', 'employees'],
            'work_status.view' => ['Work Status View', 'employees'],
            'work_status.manage' => ['Work Status Manage', 'employees'],
            'attendance.view' => ['Attendance View', 'employees'],
            'attendance.manage' => ['Attendance Manage', 'employees'],
            'notices.view' => ['Notices View', 'employees'],
            'notices.manage' => ['Notices Manage', 'employees'],
            'payroll.view' => ['Payroll View', 'payroll'],
            'payroll.manage' => ['Payroll Manage', 'payroll'],
            'payroll.approve' => ['Payroll Approve', 'payroll'],
            'payroll.pay' => ['Payroll Pay', 'payroll'],
            'final_settlement.view' => ['Final Settlement View', 'payroll'],
            'final_settlement.manage' => ['Final Settlement Manage', 'payroll'],
            'business_management.view' => ['Business Management View', 'business_management'],
            'business_management.manage' => ['Business Management Manage', 'business_management'],
            'business_managers.view' => ['Business Managers View', 'business_management'],
            'business_managers.manage' => ['Business Managers Manage', 'business_management'],
            'ad_accounts.view' => ['Ad Accounts View', 'business_management'],
            'ad_accounts.manage' => ['Ad Accounts Manage', 'business_management'],
            'ad_account_ledger.view' => ['Ad Account Ledger View', 'business_management'],
            'pages.view' => ['Pages View', 'business_management'],
            'pages.manage' => ['Pages Manage', 'business_management'],
            'campaigns.view' => ['Campaigns View', 'business_management'],
            'campaigns.manage' => ['Campaigns Manage', 'business_management'],
            'daily_performance.view' => ['Daily Performance View', 'agency_operations'],
            'daily_performance.manage' => ['Daily Performance Manage', 'agency_operations'],
            'finance.view' => ['Finance View', 'finance'],
            'finance.manage' => ['Finance Manage', 'finance'],
            'finance_accounts.view' => ['Finance Accounts View', 'finance'],
            'finance_accounts.manage' => ['Finance Accounts Manage', 'finance'],
            'cards.view' => ['Cards View', 'finance'],
            'cards.manage' => ['Cards Manage', 'finance'],
            'reconciliation.view' => ['Reconciliation View', 'finance'],
            'reconciliation.manage' => ['Reconciliation Manage', 'finance'],
            'system_tools.view' => ['System Tools View', 'system_tools'],
            'automation.view' => ['Automation View', 'system_tools'],
            'automation.manage' => ['Automation Manage', 'system_tools'],
            'documents.view' => ['Documents View', 'system_tools'],
            'documents.manage' => ['Documents Manage', 'system_tools'],
            'activity_log.view' => ['Activity Log View', 'system_tools'],
            'security_audit.view' => ['Security Audit View', 'system_tools'],
            'permissions.manage' => ['Permissions Manage', 'system_tools'],
            'test_data_reset.manage' => ['Test Data Reset Manage', 'system_tools'],
        ];
    }

    public function roleDefaults(): array
    {
        return [
            'agency_owner' => [
                'admin_dashboard.view', 'agency_operations.view', 'clients.view', 'client_payments.view', 'client_funds.view',
                'employees.view', 'notices.view', 'payroll.view', 'final_settlement.view', 'business_management.view', 'business_managers.view',
                'ad_accounts.view', 'ad_account_ledger.view', 'pages.view', 'campaigns.view',
                'daily_performance.view', 'finance.view', 'finance_accounts.view', 'cards.view',
                'reconciliation.view', 'documents.view',
            ],
            'agency_operations_manager' => [
                'admin_dashboard.view', 'agency_operations.view', 'agency_operations.manage', 'agency_operations.verify', 'agency_operations.approve',
                'moderator_operations.view', 'moderator_operations.manage', 'ad_manager_operations.view', 'ad_manager_operations.manage',
                'auditor_operations.view', 'auditor_operations.manage', 'monitor_operations.view', 'monitor_operations.manage',
                'daily_performance.view', 'daily_performance.manage', 'pages.view', 'campaigns.view',
            ],
            'moderator' => ['agency_operations.view', 'moderator_operations.view', 'moderator_operations.submit'],
            'ad_manager' => ['agency_operations.view', 'ad_manager_operations.view', 'ad_manager_operations.submit', 'ad_accounts.view', 'pages.view', 'campaigns.view'],
            'auditor' => ['agency_operations.view', 'auditor_operations.view', 'auditor_operations.submit'],
            'monitor' => ['agency_operations.view', 'monitor_operations.view', 'monitor_operations.submit'],
            'trainer' => ['trainer_operations.view', 'trainer_operations.submit', 'employees.view'],
            'hr_manager' => [
                'admin_dashboard.view', 'employees.view', 'employees.manage', 'departments.manage', 'roles.manage',
                'assignments.view', 'assignments.manage', 'work_status.view', 'work_status.manage', 'attendance.view',
                'attendance.manage', 'payroll.view', 'payroll.manage', 'payroll.approve', 'final_settlement.view',
                'final_settlement.manage', 'notices.view', 'notices.manage', 'documents.view',
            ],
            'finance_manager' => [
                'admin_dashboard.view', 'finance.view', 'finance.manage', 'finance_accounts.view', 'finance_accounts.manage',
                'cards.view', 'cards.manage',
                'reconciliation.view', 'reconciliation.manage', 'clients.view', 'client_payments.view', 'client_payments.manage',
                'client_funds.view', 'client_funds.manage', 'documents.view',
            ],
            'business_manager' => [
                'business_management.view', 'business_managers.view', 'business_managers.manage', 'ad_accounts.view',
                'ad_accounts.manage', 'ad_account_ledger.view', 'documents.view',
            ],
            'page_manager' => [
                'business_management.view', 'pages.view', 'pages.manage', 'campaigns.view', 'campaigns.manage',
                'daily_performance.view', 'daily_performance.manage', 'documents.view',
            ],
        ];
    }

    private function routeRules(): array
    {
        return [
            ['patterns' => ['admin/dashboard', 'admin/executive-performance*'], 'view' => ['admin_dashboard.view']],
            ['patterns' => ['admin/notifications*'], 'view' => ['system_tools.view'], 'write' => ['system_tools.view']],
            ['patterns' => ['admin/automation/tasks/*/complete'], 'permissions' => ['automation.manage']],
            ['patterns' => ['admin/automation*'], 'view' => ['automation.view'], 'write' => ['automation.manage']],
            ['patterns' => ['admin/documents/*/download', 'admin/documents/*/preview', 'admin/documents/*/versions/*/download', 'admin/documents/*/versions/*/preview'], 'permissions' => ['documents.view']],
            ['patterns' => ['admin/documents*'], 'view' => ['documents.view'], 'write' => ['documents.manage']],
            ['patterns' => ['admin/activity-log*'], 'view' => ['activity_log.view']],
            ['patterns' => ['admin/security-audit*'], 'view' => ['security_audit.view']],
            ['patterns' => ['admin/test-data-reset*'], 'permissions' => [self::SUPER_ADMIN_ONLY]],
            ['patterns' => ['admin/bug-tracker*'], 'view' => ['system_tools.view'], 'write' => ['system_tools.view']],

            ['patterns' => ['admin/marketing-operations/moderator/operations*'], 'view' => ['moderator_operations.view'], 'write' => ['moderator_operations.submit', 'moderator_operations.manage']],
            ['patterns' => ['admin/marketing-operations/ad-manager/operations*'], 'view' => ['ad_manager_operations.view'], 'write' => ['ad_manager_operations.submit', 'ad_manager_operations.manage']],
            ['patterns' => ['admin/marketing-operations/auditor/operations*'], 'view' => ['auditor_operations.view'], 'write' => ['auditor_operations.submit', 'auditor_operations.manage']],
            ['patterns' => ['admin/marketing-operations/monitor/operations*'], 'view' => ['monitor_operations.view'], 'write' => ['monitor_operations.submit', 'monitor_operations.manage']],
            ['patterns' => ['admin/marketing-operations/agency*'], 'view' => ['agency_operations.approve'], 'write' => ['agency_operations.approve']],
            ['patterns' => ['admin/marketing-operations/settings*'], 'view' => ['agency_operations.manage'], 'write' => ['agency_operations.manage']],
            ['patterns' => ['admin/marketing-operations/reports*', 'admin/marketing-operations/verification*'], 'view' => ['agency_operations.view'], 'write' => ['agency_operations.verify', 'agency_operations.approve']],
            ['patterns' => ['admin/marketing-operations*'], 'view' => ['agency_operations.view'], 'write' => ['agency_operations.manage']],
            ['patterns' => ['admin/employee-submissions/*/approve', 'admin/employee-submissions/*/reject'], 'permissions' => ['agency_operations.verify']],
            ['patterns' => ['admin/employee-submissions/*/merge'], 'permissions' => ['agency_operations.approve', 'daily_performance.manage']],
            ['patterns' => ['admin/employee-submissions*', 'admin/performance-verification*'], 'view' => ['agency_operations.verify'], 'write' => ['agency_operations.verify']],

            ['patterns' => ['admin/facebook-dashboard'], 'view' => ['business_management.view']],
            ['patterns' => ['admin/business-managers*'], 'view' => ['business_managers.view'], 'write' => ['business_managers.manage']],
            ['patterns' => ['admin/ad-accounts*'], 'view' => ['ad_accounts.view'], 'write' => ['ad_accounts.manage']],
            ['patterns' => ['admin/ad-account-ledger*'], 'view' => ['ad_account_ledger.view']],
            ['patterns' => ['admin/ad-account-pages*'], 'view' => ['ad_accounts.view'], 'write' => ['ad_accounts.manage']],
            ['patterns' => ['admin/ad-account-cards*'], 'view' => ['ad_accounts.view'], 'write' => ['ad_accounts.manage']],
            ['patterns' => ['admin/ad-account-billing-history*'], 'view' => ['ad_accounts.view'], 'write' => ['ad_accounts.manage']],
            ['patterns' => ['admin/client-pages*'], 'view' => ['pages.view'], 'write' => ['pages.manage']],
            ['patterns' => ['admin/campaigns*'], 'view' => ['campaigns.view'], 'write' => ['campaigns.manage']],
            ['patterns' => ['admin/datasets*'], 'view' => ['campaigns.view'], 'write' => ['campaigns.manage']],
            ['patterns' => ['admin/meta-spend-snapshots*', 'admin/meta-sync-logs*', 'admin/whatsapp-logs*'], 'view' => ['daily_performance.view'], 'write' => ['daily_performance.manage']],
            ['patterns' => ['admin/daily-reports*', 'admin/profit-history*', 'admin/export/daily-reports', 'admin/export/profit-history'], 'view' => ['daily_performance.view'], 'write' => ['daily_performance.manage']],

            ['patterns' => ['admin/client-dashboard', 'admin/clients*', 'admin/client-users*', 'admin/invoices*'], 'view' => ['clients.view'], 'write' => ['clients.manage']],
            ['patterns' => ['admin/client-fund*'], 'view' => ['client_funds.view'], 'write' => ['client_funds.manage']],
            ['patterns' => ['admin/salary-payments/*/approve', 'admin/salary-payments/*/reject', 'admin/salary-payments/*/delete'], 'permissions' => ['client_payments.manage']],
            ['patterns' => ['admin/salary-payments*'], 'view' => ['client_payments.view'], 'write' => ['client_payments.manage']],
            ['patterns' => ['admin/payments/*/approve', 'admin/payments/*/reject'], 'permissions' => ['client_payments.manage']],
            ['patterns' => ['admin/payments*'], 'view' => ['client_payments.view'], 'write' => ['client_payments.manage']],

            ['patterns' => ['admin/payroll/*/approve'], 'permissions' => ['payroll.approve']],
            ['patterns' => ['admin/payroll/*/confirm-payment', 'admin/payroll/*/reverse-payment', 'admin/payroll/*/mark-paid'], 'permissions' => ['payroll.pay']],
            ['patterns' => ['admin/payroll*', 'admin/salary-month-sheet*'], 'view' => ['payroll.view'], 'write' => ['payroll.manage']],
            ['patterns' => ['admin/attendance*'], 'view' => ['attendance.view'], 'write' => ['attendance.manage']],
            ['patterns' => ['admin/work-status*'], 'view' => ['work_status.view'], 'write' => ['work_status.manage']],
            ['patterns' => ['admin/employee-notices*'], 'view' => ['notices.view'], 'write' => ['notices.manage']],
            ['patterns' => ['admin/assignments*', 'admin/employee-assignments*'], 'view' => ['assignments.view'], 'write' => ['assignments.manage']],
            ['patterns' => ['admin/departments*'], 'view' => ['departments.manage'], 'write' => ['departments.manage']],
            ['patterns' => ['admin/employee-roles*'], 'view' => ['roles.manage'], 'write' => ['roles.manage']],
            ['patterns' => ['admin/bonuses/*/approve'], 'permissions' => ['bonus.approve']],
            ['patterns' => ['admin/bonuses*'], 'view' => ['bonus.view'], 'write' => ['bonus.manage']],
            ['patterns' => ['admin/employee-kpi*'], 'view' => ['kpi.view'], 'write' => ['employees.manage']],
            ['patterns' => ['admin/leaderboard*'], 'view' => ['leaderboard.view'], 'write' => ['employees.manage']],
            ['patterns' => ['admin/performance-targets*'], 'view' => ['targets.manage'], 'write' => ['targets.manage']],
            ['patterns' => ['admin/employee-dashboard', 'admin/employees*', 'admin/salary-days*'], 'view' => ['employees.view'], 'write' => ['employees.manage']],

            ['patterns' => ['admin/financial-management'], 'view' => ['finance.view']],
            ['patterns' => ['admin/finance/accounts*'], 'view' => ['finance_accounts.view'], 'write' => ['finance_accounts.manage']],
            ['patterns' => ['admin/finance/reports/reconciliation*'], 'view' => ['reconciliation.view'], 'write' => ['reconciliation.manage']],
            ['patterns' => ['admin/finance/reports*'], 'view' => ['finance.view'], 'write' => ['finance.manage']],
            ['patterns' => ['admin/facebook-cards*', 'admin/facebook-financial*'], 'view' => ['cards.view'], 'write' => ['cards.manage']],
            ['patterns' => ['admin/payment-providers*', 'admin/provider-transactions*', 'admin/provider-fees*'], 'view' => ['cards.view'], 'write' => ['cards.manage']],
        ];
    }

    private function mode(Request $request, array $rule): string
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return 'view';
        }

        return 'write';
    }

    private function matches(Request $request, array|string $patterns): bool
    {
        foreach ((array) $patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
