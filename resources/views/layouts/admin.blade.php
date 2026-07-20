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
            min-width: 0;
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

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .nav-toggle-input,
        .nav-toggle {
            display: none;
        }

        @media (max-width: 900px) {
            .topbar {
                align-items: center;
                flex-direction: row;
                flex-wrap: wrap;
                gap: 10px;
                min-height: 64px;
                padding: 10px 14px;
            }

            .topbar-left {
                align-items: center;
                flex: 1 1 100%;
                flex-direction: row;
                gap: 12px;
                min-width: 0;
                width: 100%;
            }

            .brand {
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .department-tabs {
                flex: 1 1 auto;
                flex-wrap: nowrap;
                min-width: 0;
                overflow-x: auto;
                padding-bottom: 2px;
                scrollbar-width: thin;
                width: auto;
            }

            .department-tab {
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .nav-toggle {
                align-items: center;
                background: rgba(255,255,255,.08);
                border: 1px solid var(--line);
                border-radius: 10px;
                color: var(--text);
                cursor: pointer;
                display: inline-flex;
                font-size: 13px;
                font-weight: 800;
                gap: 8px;
                padding: 9px 12px;
            }

            .layout {
                flex-direction: column;
                min-height: calc(100vh - 112px);
            }

            .sidebar {
                border-bottom: 1px solid var(--line);
                border-right: 0;
                display: none;
                max-height: calc(100vh - 120px);
                overflow-y: auto;
                padding: 14px;
                width: 100%;
            }

            .nav-toggle-input:checked ~ .layout .sidebar {
                display: block;
            }

            .sidebar a {
                margin-bottom: 8px;
                white-space: normal;
            }

            .sidebar-section-title {
                margin: 0 0 10px;
            }

            .content {
                padding: 16px;
            }

            .table-wrap,
            .employee-table-wrap {
                position: relative;
            }

            .table-wrap::before,
            .employee-table-wrap::before {
                color: var(--muted);
                content: "Scroll table horizontally";
                display: block;
                font-size: 12px;
                font-weight: 700;
                margin-bottom: 8px;
            }
        }

        @media (max-width: 520px) {
            .topbar {
                padding: 10px 12px;
            }

            .brand {
                font-size: 16px;
            }

            .topbar > div:last-child {
                width: 100%;
            }

            .logout-btn {
                margin-left: auto;
            }
        }
    </style>
</head>

<body>
    <input class="nav-toggle-input" id="admin-nav-toggle" type="checkbox">
    @php
        $navigation = app(\App\Services\NavigationService::class)->forRequest(request());
        $sections = $navigation['sections'];
        $activeSection = $navigation['active_section'];
        $breadcrumbs = $navigation['breadcrumbs'];
        $notificationItem = collect($sections)
            ->flatMap(fn ($section) => $section['items'])
            ->firstWhere('key', 'notifications');
    @endphp

    <div class="topbar">
        <div class="topbar-left">
            <div class="brand">NSYS Agency Admin</div>

            <div class="department-tabs">
                @foreach($sections as $section)
                    <a class="department-tab {{ $section['active'] ? 'active-department' : '' }}" href="{{ $section['url'] }}">
                        {{ $section['label'] }}
                        @if($section['badge'] > 0)
                            <span class="header-count-badge">{{ $section['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
            <label class="nav-toggle" for="admin-nav-toggle">Menu</label>
            @if($notificationItem)
                <a class="department-tab {{ $notificationItem['active'] ? 'active-department' : '' }}" href="{{ $notificationItem['url'] }}" title="Notification Center">
                    Alerts
                    @if($notificationItem['badge'] > 0)
                        <span class="header-count-badge">{{ $notificationItem['badge'] }}</span>
                    @endif
                </a>
            @endif
            <form method="POST" action="/logout">
                @csrf
                <button class="logout-btn" type="submit">Logout</button>
            </form>
        </div>
    </div>

    <div class="layout">
        <div class="sidebar">
            @if($activeSection)
                <div class="sidebar-section-title">{{ $activeSection['label'] }}</div>
                @foreach($activeSection['items'] as $item)
                    <a class="{{ $item['active'] ? 'active-menu' : '' }} {{ $item['badge'] > 0 ? 'sidebar-link-with-badge' : '' }}" href="{{ $item['url'] }}">
                        <span>{{ $item['label'] }}</span>
                        @if($item['badge'] > 0)
                            <span class="{{ trim('sidebar-count-badge ' . ($item['badge_danger'] ? 'danger' : '')) }}">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            @endif
        </div>

        <div class="content">
            @if(! empty($breadcrumbs))
                <div style="color:var(--muted);font-size:13px;margin-bottom:14px;">
                    @foreach($breadcrumbs as $crumb)
                        @if(! $loop->first) <span>/</span> @endif
                        @if(! $loop->last)
                            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                        @else
                            <span>{{ $crumb['label'] }}</span>
                        @endif
                    @endforeach
                </div>
            @endif

            @if(session('success'))
                <div class="card" style="background: rgba(34,197,94,.15); border:1px solid #22c55e; color:#22c55e; margin-bottom:20px;">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="card" style="background: rgba(239,68,68,.15); border:1px solid #ef4444; color:#fecaca; margin-bottom:20px;">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any())
                <div class="card" style="background: rgba(245,158,11,.15); border:1px solid #f59e0b; color:#fde68a; margin-bottom:20px;">
                    <strong>Please fix the following:</strong>
                    <ul style="margin:10px 0 0 18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</body>
</html>
