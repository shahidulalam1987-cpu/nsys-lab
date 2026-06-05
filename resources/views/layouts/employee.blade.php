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
        .badge { display: inline-block; padding: 6px 12px; border-radius: 30px; font-size: 13px; font-weight: 700; color: white; }
        .badge-success { background: var(--success); }
        .badge-warning { background: var(--warning); }
        .badge-danger { background: var(--danger); }
        @media (max-width: 900px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; display: flex; overflow-x: auto; gap: 8px; }
            .sidebar a { white-space: nowrap; margin-bottom: 0; }
            .content { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            table { min-width: 800px; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">NSYS Employee Portal</div>
        <form method="POST" action="/logout">
            @csrf
            <button class="logout-btn" type="submit">Logout</button>
        </form>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a class="{{ request()->is('employee/dashboard') ? 'active-menu' : '' }}" href="/employee/dashboard">Dashboard</a>
        </div>
        <div class="content">
            @yield('content')
        </div>
    </div>
</body>
</html>
