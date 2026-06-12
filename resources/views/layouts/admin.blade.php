<!DOCTYPE html>
<html>
<head>
    <title>NSYS Admin</title>

    <style>
        :root {
            --bg: #050814;
            --bg-2: #081226;
            --panel: rgba(255, 255, 255, 0.08);
            --line: rgba(255, 255, 255, 0.16);
            --text: #eef6ff;
            --muted: #a9b7cf;
            --blue: #2f8cff;
            --cyan: #42e8ff;
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: radial-gradient(circle at top left, #10234a, var(--bg));
            color: var(--text);
        }

        .topbar {
            height: 64px;
            background: rgba(8, 18, 38, 0.9);
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 0 28px;
            backdrop-filter: blur(14px);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .brand {
            font-size: 18px;
            font-weight: 800;
            background: linear-gradient(90deg, var(--blue), var(--cyan));
            -webkit-background-clip: text;
            color: transparent;
        }

        .department-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .department-tab {
            color: var(--muted);
            text-decoration: none;
            padding: 9px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: rgba(255,255,255,.06);
            font-size: 13px;
            font-weight: 700;
        }

        .department-tab:hover,
        .department-tab.active-department {
            color: white;
            background: linear-gradient(90deg, var(--blue), var(--cyan));
            box-shadow: 0 10px 30px rgba(47, 140, 255, 0.25);
        }

        .header-count-badge {
            background: var(--danger);
            border-radius: 999px;
            color: #fff;
            display: inline-block;
            font-size: 11px;
            line-height: 1;
            margin-left: 6px;
            min-width: 20px;
            padding: 4px 6px;
            text-align: center;
        }

        .logout-btn {
            color: var(--text);
            background: var(--panel);
            border: 1px solid var(--line);
            padding: 8px 14px;
            border-radius: 10px;
            cursor: pointer;
        }

        .layout {
            display: flex;
            min-height: calc(100vh - 64px);
        }

        .sidebar {
            width: 260px;
            background: rgba(8, 18, 38, 0.82);
            border-right: 1px solid var(--line);
            padding: 22px;
            backdrop-filter: blur(16px);
        }

        .sidebar a {
            display: block;
            color: var(--muted);
            text-decoration: none;
            padding: 13px 14px;
            border-radius: 12px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .sidebar a:hover,
        .sidebar a.active-menu {
            color: white;
            background: linear-gradient(90deg, var(--blue), var(--cyan));
            box-shadow: 0 10px 30px rgba(47, 140, 255, 0.25);
        }

        .sidebar a.sidebar-muted {
            cursor: default;
            opacity: .55;
        }

        .sidebar a.sidebar-muted:hover {
            background: rgba(255,255,255,.06);
            box-shadow: none;
            color: var(--muted);
        }

        .sidebar-section-title {
            color: var(--cyan);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            margin: 18px 0 8px;
            text-transform: uppercase;
        }

        .sidebar-section-title:first-child {
            margin-top: 0;
        }

        .sidebar-link-with-badge {
            align-items: center;
            display: flex !important;
            gap: 10px;
            justify-content: space-between;
        }

        .sidebar-count-badge {
            background: rgba(47, 140, 255, .35);
            border-radius: 999px;
            color: #fff;
            font-size: 11px;
            line-height: 1;
            min-width: 22px;
            padding: 5px 7px;
            text-align: center;
        }

        .sidebar-count-badge.danger {
            background: var(--danger);
        }

        .content {
            flex: 1;
            padding: 30px;
        }

        h1, h2, h3 {
            color: var(--text);
        }

        p {
            color: var(--muted);
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            padding: 22px;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            margin-bottom: 22px;
            backdrop-filter: blur(16px);
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            background: linear-gradient(90deg, var(--blue), var(--cyan));
            color: white;
            text-decoration: none;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 700;
        }

        .btn.sidebar-muted {
            background: rgba(255,255,255,.08);
            border: 1px solid var(--line);
            box-shadow: none;
            color: var(--muted);
            cursor: default;
        }

        .btn-success {
            background: #22c55e !important;
            color: #fff !important;
            border: none;
        }

        .btn-success:hover {
            background: #16a34a !important;
        }

        .btn-danger {
            background: #ef4444 !important;
            color: #fff !important;
            border: none;
        }

        .btn-danger:hover {
            background: #dc2626 !important;
        }

        input, select, textarea {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.08);
            color: var(--text);
            margin: 5px;
        }

        option {
            background: var(--bg-2);
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid var(--line);
            text-align: left;
        }

        th {
            color: var(--cyan);
            background: rgba(255,255,255,.05);
        }

        tr:hover {
            background: rgba(255,255,255,.05);
        }

        a {
            color: var(--cyan);
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            color: white;
        }

        .badge-success { background: var(--success); }
        .badge-warning { background: var(--warning); }
        .badge-danger { background: var(--danger); }
        .badge-info { background: var(--blue); }
        .badge-neutral { background: #64748b; }

        button {
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid var(--line);
            cursor: pointer;
        }
        .table-wrap {
    width: 100%;
    overflow-x: auto;
}

@media (max-width: 900px) {
    .topbar {
        height: auto;
        align-items: flex-start;
        flex-direction: column;
        padding: 14px;
    }

    .topbar-left {
        width: 100%;
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    .department-tabs {
        width: 100%;
        overflow-x: auto;
    }

    .layout {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        display: flex;
        overflow-x: auto;
        gap: 8px;
    }

    .sidebar a {
        white-space: nowrap;
        margin-bottom: 0;
    }

    .sidebar-section-title {
        align-self: center;
        margin: 0 4px 0 12px;
        white-space: nowrap;
    }

    .content {
        padding: 16px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    table {
        min-width: 800px;
    }
}

@media (max-width: 520px) {
    .topbar {
        padding: 0 14px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 16px;
    }
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin: 20px 0;
}

.stat-card {
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
}

.stat-card p {
    margin: 0;
    color: #a9b7cf;
    font-size: 14px;
}

.stat-card h2 {
    margin: 10px 0 0;
    font-size: 26px;
}
.table-wrap {
    width: 100%;
    overflow-x: auto;
}

        @media (max-width: 900px) {
            .topbar {
                align-items: flex-start;
                height: auto;
                padding: 16px;
            }

            .topbar-left {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            table {
                min-width: 800px;
            }
        }
    </style>
</head>

<body>
    @php
        $isSystemTools = request()->is('admin/bug-tracker*')
            || request()->is('admin/activity-log*')
            || request()->is('admin/security-audit*')
            || request()->is('admin/test-data-reset*');
        $isClientDepartment = request()->is('admin/client-dashboard')
            || request()->is('admin/clients*')
            || request()->is('admin/client-users*')
            || request()->is('admin/client-fund*')
            || request()->is('admin/salary-payments*');
        $isEmployeeDepartment = request()->is('admin/employee-dashboard')
            || request()->is('admin/employees*')
            || request()->is('admin/salary-month-sheet*')
            || request()->is('admin/attendance*')
            || request()->is('admin/assignments*')
            || request()->is('admin/work-status*')
            || request()->is('admin/employee-notices*')
            || request()->is('admin/payroll*');
        $isTikTok = request()->is('admin/tiktok*');
        $isFinancialManagement = request()->is('admin/financial-management*')
            || request()->is('admin/facebook-cards*')
            || request()->is('admin/facebook-financial*');
        $isFacebook = request()->is('admin/facebook-dashboard')
            || request()->is('admin/business-managers*')
            || request()->is('admin/ad-accounts*')
            || request()->is('admin/ad-account-ledger*')
            || request()->is('admin/client-pages*')
            || request()->is('admin/campaigns*')
            || request()->is('admin/daily-reports*')
            || request()->is('admin/profit-history*');
        $isAdminDashboard = request()->is('admin/dashboard');
        $openBugCount = \App\Models\BugReport::where('status', 'open')->count();
        $clientFundBadges = ($isEmployeeDepartment || $isClientDepartment)
            ? app(\App\Services\ClientFundDashboardService::class)->sidebarBadges()
            : ['upcoming_salary_count' => 0, 'unpaid_salary_count' => 0, 'pending_payment_count' => 0];
        $facebookBadges = ($isFacebook || $isAdminDashboard)
            ? [
                'billing_alert_count' => \App\Models\AdAccount::all()->filter(fn ($account) => in_array($account->billingStatus(), ['upcoming', 'overdue'], true))->count(),
            ]
            : ['billing_alert_count' => 0];
        $financialBadges = ($isFinancialManagement || $isAdminDashboard)
            ? [
                'low_card_count' => \App\Models\FacebookCard::all()->filter(fn ($card) => $card->effectiveStatus() === 'low_balance')->count(),
                'low_funding_count' => \App\Models\FundingBalance::all()->filter(fn ($balance) => $balance->isLowBalance())->count(),
            ]
            : ['low_card_count' => 0, 'low_funding_count' => 0];
    @endphp

    <div class="topbar">
        <div class="topbar-left">
            <div class="brand">NSYS Agency Admin</div>

            <div class="department-tabs">
                <a class="department-tab {{ $isAdminDashboard ? 'active-department' : '' }}" href="/admin/dashboard">Agency Dashboard</a>
                <a class="department-tab {{ $isFinancialManagement ? 'active-department' : '' }}" href="/admin/financial-management">
                    Finance
                    @if(($financialBadges['low_card_count'] + $financialBadges['low_funding_count']) > 0)
                        <span class="header-count-badge">{{ $financialBadges['low_card_count'] + $financialBadges['low_funding_count'] }}</span>
                    @endif
                </a>
                <a class="department-tab {{ $isClientDepartment ? 'active-department' : '' }}" href="/admin/client-dashboard">Clients</a>
                <a class="department-tab {{ $isEmployeeDepartment ? 'active-department' : '' }}" href="/admin/employee-dashboard">Employees</a>
                <a class="department-tab {{ $isFacebook ? 'active-department' : '' }}" href="/admin/facebook-dashboard">
                    Facebook
                    @if($facebookBadges['billing_alert_count'] > 0)
                        <span class="header-count-badge">{{ $facebookBadges['billing_alert_count'] }}</span>
                    @endif
                </a>
                <a class="department-tab {{ $isTikTok ? 'active-department' : '' }}" href="/admin/tiktok">TikTok</a>
                <a class="department-tab {{ $isSystemTools ? 'active-department' : '' }}" href="/admin/bug-tracker">
                    System Tools
                    @if($openBugCount > 0)
                        <span class="header-count-badge">{{ $openBugCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        <form method="POST" action="/logout">
            @csrf
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>

    <div class="layout">
        <div class="sidebar">
            @if($isSystemTools)
                <div class="sidebar-section-title">System Tools</div>
                <a class="{{ request()->is('admin/bug-tracker*') ? 'active-menu' : '' }}" href="/admin/bug-tracker">Bug Tracker</a>
                <a class="{{ request()->is('admin/activity-log*') ? 'active-menu' : '' }}" href="/admin/activity-log">Activity Log</a>
                <a class="{{ request()->is('admin/security-audit*') ? 'active-menu' : '' }}" href="/admin/security-audit">Security Audit</a>
                <a class="{{ request()->is('admin/test-data-reset*') ? 'active-menu' : '' }}" href="/admin/test-data-reset">Test Data Reset</a>
                <div class="sidebar-section-title">Future Ready</div>
                <a class="sidebar-muted" href="#" onclick="return false;">Backup</a>
                <a class="sidebar-muted" href="#" onclick="return false;">System Health</a>
                <a class="sidebar-muted" href="#" onclick="return false;">Error Logs</a>
            @elseif($isTikTok)
                <div class="sidebar-section-title">TikTok</div>
                <a class="{{ request()->is('admin/tiktok') ? 'active-menu' : '' }}" href="/admin/tiktok">Dashboard</a>
                <a class="{{ request()->is('admin/tiktok/ad-accounts') ? 'active-menu' : '' }}" href="/admin/tiktok/ad-accounts">Ad Account Management</a>
                <a class="{{ request()->is('admin/tiktok/pages') ? 'active-menu' : '' }}" href="/admin/tiktok/pages">Page Management</a>
                <a class="{{ request()->is('admin/tiktok/campaigns') ? 'active-menu' : '' }}" href="/admin/tiktok/campaigns">Campaign Management</a>
                <a class="{{ request()->is('admin/tiktok/daily-performance') ? 'active-menu' : '' }}" href="/admin/tiktok/daily-performance">Daily Performance Entry</a>
                <a class="{{ request()->is('admin/tiktok/analytics') ? 'active-menu' : '' }}" href="/admin/tiktok/analytics">Analytics Dashboard</a>
            @elseif($isFinancialManagement)
                <div class="sidebar-section-title">Finance</div>

                <div class="sidebar-section-title">Funding</div>
                <a class="sidebar-link-with-badge {{ request()->is('admin/facebook-financial/funding-dashboard*') ? 'active-menu' : '' }}" href="/admin/facebook-financial/funding-dashboard">
                    <span>Funding Dashboard</span>
                    @if($financialBadges['low_funding_count'] > 0)
                        <span class="sidebar-count-badge danger">{{ $financialBadges['low_funding_count'] }}</span>
                    @endif
                </a>
                <a class="{{ request()->is('admin/facebook-financial/binance-purchases*') ? 'active-menu' : '' }}" href="/admin/facebook-financial/binance-purchases">Binance Purchases</a>
                <a class="sidebar-link-with-badge {{ request()->is('admin/facebook-cards*') ? 'active-menu' : '' }}" href="/admin/facebook-cards">
                    <span>Card Management</span>
                    @if($financialBadges['low_card_count'] > 0)
                        <span class="sidebar-count-badge danger">{{ $financialBadges['low_card_count'] }}</span>
                    @endif
                </a>
                <a class="{{ request()->is('admin/facebook-financial/card-loads*') ? 'active-menu' : '' }}" href="/admin/facebook-financial/card-loads">Card Loads</a>
                <a class="{{ request()->is('admin/facebook-financial/card-transactions*') ? 'active-menu' : '' }}" href="/admin/facebook-financial/card-transactions">Card Transactions</a>

                <div class="sidebar-section-title">Finance</div>
                <a class="{{ request()->is('admin/facebook-financial/profit-dashboard*') ? 'active-menu' : '' }}" href="/admin/facebook-financial/profit-dashboard">Profit Dashboard</a>
                <a class="sidebar-muted" href="#" onclick="return false;">Revenue Dashboard</a>
                <a class="sidebar-muted" href="#" onclick="return false;">Expense Dashboard</a>

                <div class="sidebar-section-title">Reports</div>
                <a class="sidebar-muted" href="#" onclick="return false;">Monthly Profit Report</a>
                <a class="sidebar-muted" href="#" onclick="return false;">Funding Report</a>
                <a class="sidebar-muted" href="#" onclick="return false;">Card Fee Report</a>
            @elseif($isClientDepartment)
                <div class="sidebar-section-title">Clients</div>

                <div class="sidebar-section-title">Client Management</div>
                <a class="{{ request()->is('admin/client-dashboard') ? 'active-menu' : '' }}" href="/admin/client-dashboard">Client Dashboard</a>
                <a class="{{ request()->is('admin/clients') || request()->is('admin/clients/create') ? 'active-menu' : '' }}" href="/admin/clients">Client List</a>
                <a class="{{ request()->is('admin/client-users*') ? 'active-menu' : '' }}" href="/admin/client-users">Client Users</a>
                <a class="{{ request()->is('admin/clients/*') ? 'active-menu' : '' }}" href="/admin/clients">Client Details</a>

                <div class="sidebar-section-title">Client Fund</div>
                <a class="{{ request()->is('admin/client-fund*') ? 'active-menu' : '' }}" href="/admin/client-fund">Dashboard</a>
                <a class="{{ request()->is('admin/salary-payments/create') ? 'active-menu' : '' }}" href="/admin/salary-payments/create">Receive Payment</a>
                <a class="sidebar-link-with-badge {{ request()->is('admin/salary-payments/pending') ? 'active-menu' : '' }}" href="/admin/salary-payments/pending">
                    <span>Pending Payments</span>
                    @if($clientFundBadges['pending_payment_count'] > 0)
                        <span class="sidebar-count-badge danger">{{ $clientFundBadges['pending_payment_count'] }}</span>
                    @endif
                </a>
                <a class="{{ request()->is('admin/salary-payments') && ! request()->filled('status') ? 'active-menu' : '' }}" href="/admin/salary-payments">Payment History</a>

                <div class="sidebar-section-title">Client Portal</div>
                <a class="sidebar-muted" href="#" onclick="return false;">Client Portal</a>
            @elseif($isEmployeeDepartment)
                <div class="sidebar-section-title">Employees</div>

                <div class="sidebar-section-title">Employee Management</div>
                <a class="{{ request()->is('admin/employee-dashboard') ? 'active-menu' : '' }}" href="/admin/employee-dashboard">Employee Dashboard</a>
                <a class="{{ request()->is('admin/employees*') ? 'active-menu' : '' }}" href="/admin/employees">Employee List</a>

                <div class="sidebar-section-title">Assignments</div>
                <a class="{{ request()->is('admin/assignments*') ? 'active-menu' : '' }}" href="/admin/assignments">Assignment Management</a>

                <div class="sidebar-section-title">Operations</div>
                <a class="{{ request()->is('admin/work-status*') ? 'active-menu' : '' }}" href="/admin/work-status">Work Status</a>
                <a class="{{ request()->is('admin/attendance*') ? 'active-menu' : '' }}" href="/admin/attendance">Attendance</a>

                <div class="sidebar-section-title">Employee Portal</div>
                <a class="{{ request()->is('admin/employee-notices*') ? 'active-menu' : '' }}" href="/admin/employee-notices">Notice Board</a>

                <div class="sidebar-section-title">Payroll</div>
                <a class="{{ request()->is('admin/payroll*') && ! request()->filled('status') ? 'active-menu' : '' }}" href="/admin/payroll">Salary Generate</a>
                <a class="{{ request()->is('admin/salary-month-sheet*') ? 'active-menu' : '' }}" href="/admin/salary-month-sheet">Salary Report</a>
                <a class="sidebar-link-with-badge {{ request()->is('admin/payroll') && request('status') === 'upcoming' ? 'active-menu' : '' }}" href="/admin/payroll?status=upcoming">
                    <span>Upcoming Salary</span>
                    @if($clientFundBadges['upcoming_salary_count'] > 0)
                        <span class="sidebar-count-badge">{{ $clientFundBadges['upcoming_salary_count'] }}</span>
                    @endif
                </a>
                <a class="{{ request()->is('admin/payroll') && request('status') === 'paid' ? 'active-menu' : '' }}" href="/admin/payroll?status=paid">Paid Salary</a>
                <a class="sidebar-link-with-badge {{ request()->is('admin/payroll') && request('status') === 'due' ? 'active-menu' : '' }}" href="/admin/payroll?status=due">
                    <span>Unpaid Salary</span>
                    @if($clientFundBadges['unpaid_salary_count'] > 0)
                        <span class="sidebar-count-badge danger">{{ $clientFundBadges['unpaid_salary_count'] }}</span>
                    @endif
                </a>
            @elseif($isFacebook)
                <div class="sidebar-section-title">Facebook</div>
                <div class="sidebar-section-title">Facebook Dashboard</div>
                <a class="{{ request()->is('admin/facebook-dashboard') ? 'active-menu' : '' }}" href="/admin/facebook-dashboard">Dashboard</a>
                <a class="{{ request()->is('admin/business-managers*') ? 'active-menu' : '' }}" href="/admin/business-managers">BM Management</a>
                <a class="sidebar-link-with-badge {{ request()->is('admin/ad-accounts*') ? 'active-menu' : '' }}" href="/admin/ad-accounts">
                    <span>Ad Account Management</span>
                    @if($facebookBadges['billing_alert_count'] > 0)
                        <span class="sidebar-count-badge danger">{{ $facebookBadges['billing_alert_count'] }}</span>
                    @endif
                </a>
                <a class="{{ request()->is('admin/ad-account-ledger*') ? 'active-menu' : '' }}" href="/admin/ad-account-ledger">Ad Account Ledger</a>
                <a class="{{ request()->is('admin/client-pages*') ? 'active-menu' : '' }}" href="/admin/client-pages">Page Management</a>
                <a class="{{ request()->is('admin/campaigns*') ? 'active-menu' : '' }}" href="/admin/campaigns">Campaign Management</a>
                <a class="{{ request()->is('admin/daily-reports*') ? 'active-menu' : '' }}" href="/admin/daily-reports">Daily Performance Entry</a>
                <a class="{{ request()->is('admin/profit-history') ? 'active-menu' : '' }}" href="/admin/profit-history">Analytics Dashboard</a>
                <a class="sidebar-muted" href="#" onclick="return false;">Orders & Leads</a>
                <a class="sidebar-muted" href="#" onclick="return false;">Client Reports</a>
            @else
                <div class="sidebar-section-title">Agency Dashboard</div>
                <a class="{{ request()->is('admin/dashboard') ? 'active-menu' : '' }}" href="/admin/dashboard">Overview</a>
            @endif
        </div>

        <div class="content">
            @if(session('success'))
    <div class="card" style="
        background: rgba(34,197,94,.15);
        border:1px solid #22c55e;
        color:#22c55e;
        margin-bottom:20px;
    ">
        {{ session('success') }}
    </div>
@endif
            @yield('content')
        </div>
    </div>
</body>
</html>
