@extends('layouts.admin')

@section('content')
    <h1>Employee Management</h1>

    <style>
        .employee-page-header {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .employee-summary-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(6, minmax(120px, 1fr));
            margin: 14px 0 18px;
        }

        .employee-summary-card {
            background: #111827;
            border: 1px solid #243044;
            border-radius: 8px;
            padding: 12px;
        }

        .employee-summary-card p {
            color: #94a3b8;
            font-size: 12px;
            margin: 0 0 6px;
        }

        .employee-summary-card h2 {
            font-size: 22px;
            line-height: 1;
            margin: 0;
        }

        .employee-filter-form {
            align-items: center;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(260px, 1fr) minmax(180px, 220px) auto auto;
        }

        .employee-filter-form input,
        .employee-filter-form select {
            box-sizing: border-box;
            min-height: 42px;
            width: 100%;
        }

        .employee-filter-meta {
            color: #94a3b8;
            font-size: 13px;
            margin: 10px 0 0;
        }

        .employee-table-wrap {
            overflow-x: auto;
        }

        .employee-table {
            min-width: 980px;
            width: 100%;
        }

        .employee-table th,
        .employee-table td {
            vertical-align: middle;
        }

        .employee-main-link {
            color: #dbeafe;
            font-weight: 700;
            text-decoration: none;
        }

        .employee-subtext {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        .status-badge,
        .login-badge {
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            padding: 7px 10px;
            white-space: nowrap;
        }

        .status-active {
            background: rgba(34, 197, 94, 0.16);
            color: #86efac;
        }

        .status-probation {
            background: rgba(59, 130, 246, 0.16);
            color: #93c5fd;
        }

        .status-on_leave {
            background: rgba(234, 179, 8, 0.18);
            color: #fde68a;
        }

        .status-inactive {
            background: rgba(148, 163, 184, 0.16);
            color: #cbd5e1;
        }

        .status-terminated {
            background: rgba(239, 68, 68, 0.16);
            color: #fca5a5;
        }

        .login-linked {
            background: rgba(34, 197, 94, 0.16);
            color: #86efac;
        }

        .login-unlinked {
            background: rgba(234, 179, 8, 0.18);
            color: #fde68a;
        }

        .employee-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .employee-actions form {
            display: inline;
            margin: 0;
        }

        .action-link,
        .action-button {
            background: #111827;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #cbd5e1;
            cursor: pointer;
            display: inline-block;
            font-size: 12px;
            line-height: 1;
            padding: 8px 10px;
            text-decoration: none;
        }

        .action-button-danger {
            border-color: rgba(239, 68, 68, 0.45);
            color: #fca5a5;
        }

        @media (max-width: 1100px) {
            .employee-summary-grid {
                grid-template-columns: repeat(3, minmax(120px, 1fr));
            }
        }

        @media (max-width: 760px) {
            .employee-summary-grid,
            .employee-filter-form {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="employee-page-header">
        <div>
            <p style="margin:0; color:#94a3b8;">Manage employee profiles, login links, and employment status.</p>
        </div>
        <a class="btn" href="/admin/employees/create">Add Employee</a>
    </div>

    <div class="employee-summary-grid">
        <div class="employee-summary-card"><p>Total</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="employee-summary-card"><p>Active</p><h2>{{ number_format($summary['active']) }}</h2></div>
        <div class="employee-summary-card"><p>Probation</p><h2>{{ number_format($summary['probation']) }}</h2></div>
        <div class="employee-summary-card"><p>On Leave</p><h2>{{ number_format($summary['on_leave']) }}</h2></div>
        <div class="employee-summary-card"><p>Inactive</p><h2>{{ number_format($summary['inactive']) }}</h2></div>
        <div class="employee-summary-card"><p>Terminated</p><h2>{{ number_format($summary['terminated']) }}</h2></div>
    </div>

    <div class="card" style="margin-top:20px;">
        <form class="employee-filter-form" method="GET" action="/admin/employees">
            <input type="text" name="search" placeholder="Employee ID, name, mobile" value="{{ request('search') }}">
            <select name="status">
                <option value="">All Employees</option>
                @foreach(\App\Models\Employee::STATUS_FILTERS as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Search</button>
            <a href="/admin/employees">Reset</a>
        </form>
        <p class="employee-filter-meta">Total Employees Found: {{ $employees->count() }}</p>
    </div>

    <div class="card">
        <div class="employee-table-wrap">
            <table class="employee-table">
                <tr>
                    <th>Employee</th>
                    <th>Mobile</th>
                    <th>Department / Role</th>
                    <th>Joining</th>
                    <th>Salary</th>
                    <th>Status</th>
                    <th>Login</th>
                    <th>Action</th>
                </tr>
                @forelse($employees as $employee)
                    <tr>
                        <td>
                            <a class="employee-main-link" href="/admin/employees/{{ $employee->id }}">{{ $employee->employee_id }}</a>
                            <div class="employee-subtext">
                                <a href="/admin/employees/{{ $employee->id }}">{{ $employee->name }}</a>
                            </div>
                        </td>
                        <td>{{ $employee->mobile ?: '-' }}</td>
                        <td>
                            <strong>{{ $employee->department }}</strong>
                            <div class="employee-subtext">{{ $employee->role }}</div>
                        </td>
                        <td>{{ $employee->joining_date?->toDateString() ?: '-' }}</td>
                        <td>BDT {{ number_format($employee->monthly_salary, 2) }}</td>
                        <td>
                            <span class="status-badge status-{{ $employee->status }}">
                                {{ $employee->statusLabel() }}
                            </span>
                        </td>
                        <td>
                            @if($employee->user_id)
                                <span class="login-badge login-linked">Linked</span>
                            @else
                                <span class="login-badge login-unlinked">Not Linked</span>
                            @endif
                        </td>
                        <td>
                            <div class="employee-actions">
                                <a class="action-link" href="/admin/employees/{{ $employee->id }}">View</a>
                                <a class="action-link" href="/admin/employees/{{ $employee->id }}/edit">Edit</a>

                                <form method="POST" action="/admin/employees/{{ $employee->id }}/terminate">
                                    @csrf
                                    <button class="action-button action-button-danger" type="submit" onclick="return confirm('Terminate this employee? History and login will be preserved.');">Terminate</button>
                                </form>

                                <form method="POST" action="/admin/employees/{{ $employee->id }}/delete">
                                    @csrf
                                    <button class="action-button action-button-danger" type="submit" onclick="return confirm('Delete this employee? This is allowed only when no history exists.');">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No employees found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
