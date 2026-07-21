@extends('layouts.admin')

@section('content')
    <style>
        .employee-dashboard-actions {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .employee-action-group h2 {
            margin-top: 0;
        }

        .employee-action-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .employee-stat-link {
            color: inherit;
            display: block;
            text-decoration: none;
        }

        .employee-stat-link:hover {
            border-color: #38bdf8;
        }

        @media (max-width: 920px) {
            .employee-dashboard-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <h1>Employee Dashboard</h1>

    <div class="stats-grid">
        <a class="stat-card employee-stat-link" href="/admin/employees">
            <p>Employees</p>
            <h2>{{ number_format($totalEmployees) }}</h2>
        </a>
        <a class="stat-card employee-stat-link" href="/admin/employees?employee_type=client_assigned">
            <p>Client Assigned Employees</p>
            <h2>{{ number_format($clientAssignedEmployees) }}</h2>
        </a>
        <a class="stat-card employee-stat-link" href="/admin/employees?employee_type=agency_internal">
            <p>Agency Internal Employees</p>
            <h2>{{ number_format($agencyInternalEmployees) }}</h2>
        </a>
        <a class="stat-card employee-stat-link" href="/admin/attendance">
            <p>Attendance</p>
            <h2>{{ number_format($attendanceRecords) }}</h2>
            <p>This Month</p>
        </a>
        <a class="stat-card employee-stat-link" href="/admin/payroll?status=upcoming">
            <p>Upcoming Salary</p>
            <h2>BDT {{ number_format($employeeDashboardAlerts['upcoming_amount'], 2) }}</h2>
            <p>{{ number_format($employeeDashboardAlerts['upcoming_count']) }} Employees</p>
        </a>
        <a class="stat-card employee-stat-link" href="/admin/payroll?status=due">
            <p>Unpaid Salary Due</p>
            <h2>BDT {{ number_format($employeeDashboardAlerts['unpaid_amount'], 2) }}</h2>
            <p>{{ number_format($employeeDashboardAlerts['unpaid_count']) }} Employees</p>
        </a>
        <a class="stat-card employee-stat-link" href="/admin/payroll?status=due&employee_scope=terminated">
            <p>Final Settlement Due</p>
            <h2>BDT {{ number_format($employeeDashboardAlerts['final_settlement_amount'], 2) }}</h2>
            <p>{{ number_format($employeeDashboardAlerts['final_settlement_count']) }} Employees</p>
        </a>
    </div>

    <div class="card">
        <h2>Department Counts</h2>
        <p>
            @forelse($departmentCounts as $department => $count)
                <span class="badge badge-info" style="margin:4px;">{{ $department }}: {{ number_format($count) }}</span>
            @empty
                No department data found.
            @endforelse
        </p>
    </div>

    <div class="employee-dashboard-actions">
        <div class="card employee-action-group">
            <h2>People</h2>
            <div class="employee-action-links">
                <a class="btn" href="/admin/employees">Employee List</a>
                <a class="btn" href="/admin/assignments">Assignments</a>
            </div>
        </div>
        <div class="card employee-action-group">
            <h2>Operations</h2>
            <div class="employee-action-links">
                <a class="btn" href="/admin/work-status">Work Status</a>
                <a class="btn" href="/admin/attendance">Attendance</a>
            </div>
        </div>
        <div class="card employee-action-group">
            <h2>Payroll</h2>
            <div class="employee-action-links">
                <a class="btn" href="/admin/payroll">Payroll Dashboard</a>
                <a class="btn" href="/admin/payroll?status=upcoming">Upcoming Salary</a>
                <a class="btn" href="/admin/payroll?status=due">Unpaid Salary</a>
                <a class="btn" href="/admin/payroll?status=due&employee_scope=terminated">Final Settlement Due</a>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Recent Employees</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Joining Date</th>
                </tr>
                @forelse($recentEmployees as $employee)
                    <tr>
                        <td><a href="/admin/employees/{{ $employee->id }}">{{ $employee->employee_id }}</a></td>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->departmentName() }}</td>
                        <td>{{ $employee->roleName() }}</td>
                        <td>{{ $employee->employeeTypeLabel() }}</td>
                        <td>{{ $employee->statusLabel() }}</td>
                        <td>{{ $employee->joining_date?->toDateString() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No employees found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

@endsection
