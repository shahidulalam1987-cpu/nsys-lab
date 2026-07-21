@extends('layouts.admin')

@section('content')
    <style>
        .performance-header {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .performance-header p {
            margin: 4px 0 0;
        }

        .performance-actions,
        .performance-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .performance-filters {
            align-items: end;
        }

        .performance-filters label {
            color: var(--muted);
            display: grid;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
        }

        .performance-kpi-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .performance-table-card {
            overflow-x: auto;
        }

        .employee-kpi-name {
            color: var(--text);
            font-weight: 800;
        }

        .employee-kpi-meta {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }

        @media (max-width: 1180px) {
            .performance-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .performance-header {
                display: block;
            }

            .performance-actions {
                margin-top: 12px;
            }

            .performance-kpi-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="performance-header">
        <div>
            <h1>Employee Performance Dashboard</h1>
            <p>Approved and merged employee performance from {{ $from->toDateString() }} to {{ $to->toDateString() }}.</p>
        </div>
        <div class="performance-actions">
            <a class="btn" href="/admin/leaderboard">Leaderboard</a>
            <a class="btn" href="/admin/performance-targets">Targets</a>
            <a class="btn" href="/admin/bonuses">Bonus Review</a>
            <a class="btn" href="/admin/employee-kpi/export?{{ http_build_query($filters) }}">Export CSV</a>
        </div>
    </div>

    <div class="performance-kpi-grid">
        <div class="stat-card">
            <p>Employees With KPI</p>
            <h2>{{ number_format($summary['employees']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Orders</p>
            <h2>{{ number_format($summary['orders']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Approved Spend</p>
            <h2>USD {{ number_format($summary['approved_spend'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Average CPO</p>
            <h2>USD {{ number_format($summary['average_cpo'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Approval Rate</p>
            <h2>{{ number_format($summary['approval_rate'], 2) }}%</h2>
        </div>
    </div>

    <div class="card">
        <form class="performance-filters" method="GET">
            <label>
                From
                <input type="date" name="date_from" value="{{ $from->toDateString() }}">
            </label>
            <label>
                To
                <input type="date" name="date_to" value="{{ $to->toDateString() }}">
            </label>
            <label>
                Employee
                <select name="employee_id">
                    <option value="">All Employees</option>
                    @foreach($employees as $item)
                        <option value="{{ $item->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Department
                <select name="department_id">
                    <option value="">All Departments</option>
                    @foreach($departments as $item)
                        <option value="{{ $item->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Role
                <select name="role_id">
                    <option value="">All Roles</option>
                    @foreach($roles as $item)
                        <option value="{{ $item->id }}" @selected((string) ($filters['role_id'] ?? '') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn">Filter</button>
            <a class="btn sidebar-muted" href="/admin/employee-kpi">Reset</a>
        </form>
    </div>

    <div class="card performance-table-card">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Orders</th>
                    <th>Confirmed</th>
                    <th>Approved Spend</th>
                    <th>CPO</th>
                    <th>Approval</th>
                    <th>Pages</th>
                    <th>Active / Missing</th>
                    <th>Target</th>
                    <th>Profit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            <div class="employee-kpi-name">{{ $row['employee']->name }}</div>
                            <div class="employee-kpi-meta">{{ $row['employee']->roleName() }} | {{ $row['employee']->departmentName() }}</div>
                        </td>
                        <td>{{ number_format($row['total_orders']) }}</td>
                        <td>{{ number_format($row['confirmed_orders']) }}</td>
                        <td>USD {{ number_format($row['approved_spend'], 2) }}</td>
                        <td>USD {{ number_format($row['average_cpo'], 2) }}</td>
                        <td>{{ number_format($row['approval_rate'], 2) }}%</td>
                        <td>{{ number_format($row['pages_handled']) }}</td>
                        <td>{{ number_format($row['active_days']) }} / {{ number_format($row['missing_days']) }}</td>
                        <td>{{ number_format($row['target_achievement'], 2) }}%</td>
                        <td>BDT {{ number_format($row['profit_contribution'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">No KPI data found for the selected period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
