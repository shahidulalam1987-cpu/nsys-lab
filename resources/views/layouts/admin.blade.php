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
    table {
        min-width: 800px;
    }
}
    </style>
</head>

<body>
    @php
        $isEmployeeDepartment = request()->is('admin/employee-dashboard')
            || request()->is('admin/employees*')
            || request()->is('admin/salary-payments*')
            || request()->is('admin/salary-month-sheet*')
            || request()->is('admin/payroll*');
    @endphp

    <div class="topbar">
        <div class="topbar-left">
            <div class="brand">NSYS Agency Admin</div>

            <div class="department-tabs">
                <a class="department-tab {{ ! $isEmployeeDepartment ? 'active-department' : '' }}" href="/admin/dashboard">Boosting Department</a>
                <a class="department-tab {{ $isEmployeeDepartment ? 'active-department' : '' }}" href="/admin/employee-dashboard">Employee Department</a>
            </div>
        </div>

        <form method="POST" action="/logout">
            @csrf
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>

    <div class="layout">
        <div class="sidebar">
            @if($isEmployeeDepartment)
                <a class="{{ request()->is('admin/employees*') ? 'active-menu' : '' }}" href="/admin/employees">Employee Management</a>
                <a class="{{ request()->is('admin/payroll*') ? 'active-menu' : '' }}" href="/admin/payroll">Salary Generate</a>
                <a class="{{ request()->is('admin/salary-month-sheet*') ? 'active-menu' : '' }}" href="/admin/salary-month-sheet">Salary Report</a>
                <a class="{{ request()->is('admin/salary-payments') ? 'active-menu' : '' }}" href="/admin/salary-payments">Salary Payments</a>
                <a class="{{ request()->is('admin/salary-payments/pending') ? 'active-menu' : '' }}" href="/admin/salary-payments/pending">Pending Salary Payments</a>
            @else
                <a class="{{ request()->is('admin/dashboard') ? 'active-menu' : '' }}" href="/admin/dashboard">Dashboard</a>
                <a class="{{ request()->is('admin/clients*') ? 'active-menu' : '' }}" href="/admin/clients">Clients</a>
                <a class="{{ request()->is('admin/client-users*') ? 'active-menu' : '' }}" href="/admin/client-users">Client Users</a>
                <a class="{{ request()->is('admin/payments') ? 'active-menu' : '' }}" href="/admin/payments">All Payments</a>
                <a class="{{ request()->is('admin/payments/pending') ? 'active-menu' : '' }}" href="/admin/payments/pending">Pending Payments</a>
                <a class="{{ request()->is('admin/invoices*') ? 'active-menu' : '' }}" href="/admin/invoices">Invoices</a>
                <a class="{{ request()->is('admin/daily-reports*') ? 'active-menu' : '' }}" href="/admin/daily-reports">Daily Reports</a>
                <a class="{{ request()->is('admin/profit-history') ? 'active-menu' : '' }}" href="/admin/profit-history">Profit History</a>
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
