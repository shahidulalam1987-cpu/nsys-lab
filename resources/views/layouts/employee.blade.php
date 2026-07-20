<!DOCTYPE html>
<html>
<head>
    <title>NSYS Employee Portal</title>
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
        body { margin: 0; font-family: Arial, sans-serif; background: radial-gradient(circle at top left, #10234a, var(--bg)); color: var(--text); }
        .topbar { height: 64px; background: rgba(8, 18, 38, 0.9); border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; padding: 0 28px; }
        .brand { font-size: 18px; font-weight: 800; background: linear-gradient(90deg, var(--blue), var(--cyan)); -webkit-background-clip: text; color: transparent; }
        .logout-btn, button { color: var(--text); background: var(--panel); border: 1px solid var(--line); padding: 8px 14px; border-radius: 10px; cursor: pointer; }
        input, select, textarea { padding: 10px 12px; border-radius: 10px; border: 1px solid var(--line); background: rgba(255,255,255,.08); color: var(--text); margin: 5px; }
        option { background: var(--bg-2); color: white; }
        .btn { display: inline-block; padding: 10px 14px; background: linear-gradient(90deg, var(--blue), var(--cyan)); color: white; text-decoration: none; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; }
        .layout { display: flex; min-height: calc(100vh - 64px); }
        .sidebar { width: 240px; background: rgba(8, 18, 38, 0.82); border-right: 1px solid var(--line); padding: 22px; }
        .sidebar a { display: block; color: var(--muted); text-decoration: none; padding: 13px 14px; border-radius: 12px; margin-bottom: 8px; font-weight: 600; }
        .sidebar a:hover, .sidebar a.active-menu { color: white; background: linear-gradient(90deg, var(--blue), var(--cyan)); }
        .content { flex: 1; padding: 30px; }
        .card, .stat-card { background: var(--panel); border: 1px solid var(--line); padding: 22px; border-radius: 18px; margin-bottom: 22px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin: 20px 0; }
        .stat-card p { margin: 0; color: var(--muted); font-size: 14px; }
        .stat-card h2 { margin: 10px 0 0; font-size: 26px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 13px; border-bottom: 1px solid var(--line); text-align: left; }
        th { color: var(--cyan); background: rgba(255,255,255,.05); }
        a { color: var(--cyan); }
        .table-wrap { width: 100%; overflow-x: auto; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 30px; font-size: 13px; font-weight: 700; color: white; }
        .badge-success { background: var(--success); }
        .badge-warning { background: var(--warning); }
        .badge-danger { background: var(--danger); }
        .nav-toggle-input, .nav-toggle { display: none; }
        @media (max-width: 900px) {
            .topbar { align-items: center; flex-wrap: wrap; gap: 10px; height: auto; min-height: 64px; padding: 10px 14px; }
            .nav-toggle { align-items: center; background: rgba(255,255,255,.08); border: 1px solid var(--line); border-radius: 10px; color: var(--text); cursor: pointer; display: inline-flex; font-size: 13px; font-weight: 800; padding: 9px 12px; }
            .layout { flex-direction: column; }
            .sidebar { border-bottom: 1px solid var(--line); border-right: 0; display: none; max-height: calc(100vh - 120px); overflow-y: auto; padding: 14px; width: 100%; }
            .nav-toggle-input:checked ~ .layout .sidebar { display: block; }
            .sidebar a { white-space: normal; margin-bottom: 8px; }
            .content { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            table { min-width: 800px; }
            .table-wrap { position: relative; }
            .table-wrap::before { color: var(--muted); content: "Scroll table horizontally"; display: block; font-size: 12px; font-weight: 700; margin-bottom: 8px; }
        }
        @media (max-width: 520px) {
            .brand { font-size: 16px; }
            .topbar form { display: flex; gap: 8px; }
        }
    </style>
</head>
<body>
    <input class="nav-toggle-input" id="employee-nav-toggle" type="checkbox">
    @php
        $portalEmployee = auth()->user()->employee;
        $canDailyOrders = $portalEmployee && (auth()->user()->hasRole('moderator') || str_contains(mb_strtolower($portalEmployee->roleName()), 'moderator'));
        $canDailySpend = $portalEmployee && (auth()->user()->hasRole('facebook_manager') || in_array(mb_strtolower($portalEmployee->roleName()), ['ad manager', 'facebook manager'], true));
    @endphp
    <div class="topbar">
        <div class="brand">NSYS Employee Portal</div>
        <form method="POST" action="/logout">
            @csrf
            <label class="nav-toggle" for="employee-nav-toggle">Menu</label>
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a class="{{ request()->is('employee/dashboard') ? 'active-menu' : '' }}" href="/employee/dashboard">Dashboard</a>
            @if($canDailyOrders)<a class="{{ request()->is('employee/daily-orders*') ? 'active-menu' : '' }}" href="/employee/daily-orders">Daily Orders</a>@endif
            @if($canDailySpend)<a class="{{ request()->is('employee/daily-spend*') ? 'active-menu' : '' }}" href="/employee/daily-spend">Daily Spend</a>@endif
            <a class="{{ request()->is('employee/performance') ? 'active-menu' : '' }}" href="/employee/performance">My Performance</a>
            <a class="{{ request()->is('employee/work-status*') ? 'active-menu' : '' }}" href="/employee/work-status">My Work Status</a>
            <a class="{{ request()->is('employee/attendance*') ? 'active-menu' : '' }}" href="/employee/attendance">My Attendance</a>
            <a class="{{ request()->is('employee/salary*') ? 'active-menu' : '' }}" href="/employee/salary">My Salary</a>
            <a class="{{ request()->is('employee/assignments') ? 'active-menu' : '' }}" href="/employee/assignments">My Assignments</a>
            <a class="{{ request()->is('employee/documents') ? 'active-menu' : '' }}" href="/employee/documents">My Documents</a>
            <a class="{{ request()->is('employee/notices') ? 'active-menu' : '' }}" href="/employee/notices">Notices</a>
            <a class="{{ request()->is('employee/profile') ? 'active-menu' : '' }}" href="/employee/profile">My Profile</a>
        </div>
        <div class="content">
            @if(session('success'))
                <div class="card" style="background: rgba(34,197,94,.15); border:1px solid #22c55e; color:#22c55e;">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="card" style="color:#ef4444;">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</body>
</html>
