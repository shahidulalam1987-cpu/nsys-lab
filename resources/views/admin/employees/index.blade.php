@extends('layouts.admin')

@section('content')
    <h1 style="margin-bottom:6px;">Employee List</h1>

    <style>
        .employee-page-header {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .employee-page-subtitle {
            color: #94a3b8;
            margin: 0;
        }

        .employee-status-nav {
            display: flex;
            gap: 6px;
            margin: 2px 0 12px;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .employee-status-tab {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 999px;
            color: #a9b7cf;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            padding: 7px 11px;
            text-decoration: none;
            white-space: nowrap;
        }

        .employee-status-tab:hover,
        .employee-status-tab.active {
            background: linear-gradient(90deg, #2f8cff, #42e8ff);
            box-shadow: 0 10px 30px rgba(47, 140, 255, .25);
            color: #fff;
        }

        .employee-summary-badges {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(6, minmax(96px, 1fr));
            margin: 0 0 12px;
        }

        .employee-summary-badge {
            align-items: center;
            background: rgba(17, 24, 39, .95);
            border: 1px solid #243044;
            border-radius: 8px;
            display: flex;
            gap: 8px;
            justify-content: center;
            min-height: 38px;
            padding: 8px 10px;
        }

        .employee-summary-badge span {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
        }

        .employee-summary-badge strong {
            color: #eef6ff;
            font-size: 15px;
            line-height: 1;
        }

        .employee-filter-form {
            align-items: center;
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(280px, 1fr) minmax(170px, 210px) auto;
        }

        .employee-filter-form input,
        .employee-filter-form select {
            box-sizing: border-box;
            margin: 0;
            min-height: 38px;
            width: 100%;
        }

        .employee-filter-card {
            margin: 0 0 14px;
            padding: 14px;
        }

        .employee-filter-reset {
            color: #42e8ff;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
            margin-top: 8px;
            text-decoration: none;
        }

        .employee-filter-meta {
            color: #94a3b8;
            font-size: 13px;
            margin: 8px 0 0;
        }

        .employee-pagination {
            margin-top: 14px;
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
            padding: 12px 12px;
        }

        .employee-table th,
        .employee-table td {
            vertical-align: middle;
        }

        .employee-table th:nth-child(4),
        .employee-table th:nth-child(5),
        .employee-table td:nth-child(4),
        .employee-table td:nth-child(5) {
            text-align: right;
        }

        .employee-table th:nth-child(6),
        .employee-table th:nth-child(7),
        .employee-table td:nth-child(6),
        .employee-table td:nth-child(7) {
            text-align: center;
        }

        .employee-id-link {
            color: #94a3b8;
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
            text-decoration: none;
        }

        .employee-name-link {
            color: #dbeafe;
            display: inline-block;
            font-size: 14px;
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
            min-width: 86px;
            padding: 7px 10px;
            text-align: center;
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
            min-width: 248px;
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
            font-weight: 700;
            line-height: 30px;
            min-height: 32px;
            padding: 0 10px;
            text-decoration: none;
        }

        .action-link:hover,
        .action-button:hover {
            border-color: #42e8ff;
            color: #eef6ff;
        }

        .action-button-warning {
            border-color: rgba(245, 158, 11, 0.55);
            color: #fcd34d;
        }

        .action-button-danger {
            border-color: rgba(239, 68, 68, 0.45);
            color: #fca5a5;
        }

        .employee-table-card {
            padding: 16px;
        }

        @media (max-width: 1180px) {
            .employee-summary-badges {
                grid-template-columns: repeat(3, minmax(96px, 1fr));
            }
        }

        @media (max-width: 760px) {
            .employee-filter-form {
                grid-template-columns: 1fr;
            }

            .employee-status-nav {
                flex-wrap: nowrap;
            }

            .employee-summary-badges {
                grid-template-columns: repeat(2, minmax(120px, 1fr));
            }
        }
    </style>

    <div class="employee-page-header">
        <div>
            <p class="employee-page-subtitle">Manage employee records, status, login access, and salary information.</p>
        </div>
        <a class="btn" href="/admin/employees/create">Add Employee</a>
    </div>

    <div class="employee-status-nav" aria-label="Employee status filters">
        <a class="employee-status-tab {{ request()->filled('status') ? '' : 'active' }}" href="/admin/employees">All</a>
        @foreach(\App\Models\Employee::STATUS_FILTERS as $value => $label)
            <a class="employee-status-tab {{ request('status') === $value ? 'active' : '' }}" href="/admin/employees?status={{ $value }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="employee-summary-badges" aria-label="Employee status summary">
        <div class="employee-summary-badge"><span>Total</span><strong>{{ number_format($summary['total']) }}</strong></div>
        <div class="employee-summary-badge"><span>Active</span><strong>{{ number_format($summary['active']) }}</strong></div>
        <div class="employee-summary-badge"><span>Probation</span><strong>{{ number_format($summary['probation']) }}</strong></div>
        <div class="employee-summary-badge"><span>Leave</span><strong>{{ number_format($summary['on_leave']) }}</strong></div>
        <div class="employee-summary-badge"><span>Inactive</span><strong>{{ number_format($summary['inactive']) }}</strong></div>
        <div class="employee-summary-badge"><span>Terminated</span><strong>{{ number_format($summary['terminated']) }}</strong></div>
        <div class="employee-summary-badge"><span>Client Assigned</span><strong>{{ number_format($summary['client_assigned']) }}</strong></div>
        <div class="employee-summary-badge"><span>Agency Internal</span><strong>{{ number_format($summary['agency_internal']) }}</strong></div>
    </div>

    <div class="card employee-filter-card">
        <form class="employee-filter-form" method="GET" action="/admin/employees">
            <input type="text" name="search" placeholder="Search Employee" value="{{ request('search') }}">
            <select name="status">
                <option value="">All Employees</option>
                @foreach(\App\Models\Employee::STATUS_FILTERS as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="employee_type">
                <option value="">All Types</option>
                @foreach(\App\Models\Employee::EMPLOYEE_TYPES as $value => $label)
                    <option value="{{ $value }}" {{ request('employee_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="department_id">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ (int) request('department_id') === $department->id ? 'selected' : '' }}>
                        {{ $department->name }}{{ $department->status === 'inactive' ? ' (Inactive)' : '' }}
                    </option>
                @endforeach
            </select>
            <select name="role_id">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ (int) request('role_id') === $role->id ? 'selected' : '' }}>
                        {{ $role->name }}{{ $role->status === 'inactive' ? ' (Inactive)' : '' }}
                    </option>
                @endforeach
            </select>
            <select name="salary_source">
                <option value="">All Salary Sources</option>
                @foreach(\App\Models\Employee::SALARY_SOURCES as $value => $label)
                    <option value="{{ $value }}" {{ request('salary_source') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Search</button>
        </form>
        <a class="employee-filter-reset" href="/admin/employees">Reset</a>
        <p class="employee-filter-meta">Total Employees Found: {{ $employees->total() }}</p>
    </div>

    <div class="card employee-table-card">
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
                    @php
                        $hasHistory = $employee->assignments_exists || $employee->salary_days_exists || $employee->payrolls_exists;
                    @endphp
                    <tr>
                        <td>
                            <a class="employee-id-link" href="/admin/employees/{{ $employee->id }}">{{ $employee->employee_id }}</a>
                            <div><a class="employee-name-link" href="/admin/employees/{{ $employee->id }}">{{ $employee->name }}</a></div>
                            <div class="employee-subtext">{{ $employee->employeeTypeLabel() }}</div>
                        </td>
                        <td>{{ $employee->mobile ?: '-' }}</td>
                        <td>
                            <strong>{{ $employee->departmentName() }}</strong>
                            <div class="employee-subtext">{{ $employee->roleName() }}</div>
                            <div class="employee-subtext">{{ $employee->salarySourceLabel() }}</div>
                        </td>
                        <td>{{ $employee->joining_date?->toDateString() ?: '-' }}</td>
                        <td>BDT {{ number_format($employee->monthly_salary, 2) }}</td>
                        <td>
                            <span class="status-badge status-{{ $employee->status }}">
                                {{ $employee->shortStatusLabel() }}
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

                                @if($employee->status !== 'terminated')
                                    <form method="POST" action="/admin/employees/{{ $employee->id }}/terminate">
                                        @csrf
                                        <button class="action-button action-button-warning" type="submit" onclick="return confirm('Terminate this employee? History and login will be preserved.');">Terminate</button>
                                    </form>
                                @endif

                                @if($hasHistory)
                                    <span class="action-button" title="Employee has assignments, salary days, or payroll history. Terminate instead of deleting.">Protected</span>
                                @else
                                    <form method="POST" action="/admin/employees/{{ $employee->id }}/delete">
                                        @csrf
                                        <button class="action-button action-button-danger" type="submit" onclick="return confirm('Delete this employee? This is allowed only when no history exists.');">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No employees found.</td></tr>
                @endforelse
            </table>
        </div>
        <div class="employee-pagination">
            {{ $employees->links() }}
        </div>
    </div>
@endsection
