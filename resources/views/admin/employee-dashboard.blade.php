@extends('layouts.admin')

@section('content')
    <h1>Employees</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Employees</p>
            <h2>{{ number_format($totalEmployees) }}</h2>
        </div>
        <div class="stat-card">
            <p>Client Assigned Employees</p>
            <h2>{{ number_format($clientAssignedEmployees) }}</h2>
        </div>
        <div class="stat-card">
            <p>Agency Internal Employees</p>
            <h2>{{ number_format($agencyInternalEmployees) }}</h2>
        </div>
        <div class="stat-card">
            <p>Attendance</p>
            <h2>{{ number_format($attendanceRecords) }}</h2>
            <p>This Month</p>
        </div>
        <div class="stat-card">
            <p>Upcoming Salary</p>
            <h2>BDT {{ number_format($employeeDashboardAlerts['upcoming_amount'], 2) }}</h2>
            <p>{{ number_format($employeeDashboardAlerts['upcoming_count']) }} Employees</p>
        </div>
        <div class="stat-card">
            <p>Unpaid Salary Due</p>
            <h2>BDT {{ number_format($employeeDashboardAlerts['unpaid_amount'], 2) }}</h2>
            <p>{{ number_format($employeeDashboardAlerts['unpaid_count']) }} Employees</p>
        </div>
        <div class="stat-card">
            <p>Final Settlement Due</p>
            <h2>BDT {{ number_format($employeeDashboardAlerts['final_settlement_amount'], 2) }}</h2>
            <p>{{ number_format($employeeDashboardAlerts['final_settlement_count']) }} Employees</p>
        </div>
    </div>

    <div class="card">
        <h2>Department Wise Counts</h2>
        <p>
            @forelse($departmentCounts as $department => $count)
                <span class="badge badge-info" style="margin:4px;">{{ $department }}: {{ number_format($count) }}</span>
            @empty
                No department data found.
            @endforelse
        </p>
    </div>

    <div class="card">
        <a class="btn" href="/admin/employees">Employee List</a>
        <a class="btn" href="/admin/assignments">Assignments</a>
        <a class="btn" href="/admin/work-status">Work Status</a>
        <a class="btn" href="/admin/attendance">Attendance</a>
        <a class="btn" href="/admin/payroll">Payroll Dashboard</a>
        <a class="btn" href="/admin/payroll?status=upcoming">Upcoming Salary</a>
        <a class="btn" href="/admin/payroll?status=due">Unpaid Salary</a>
        <a class="btn" href="/admin/payroll?status=due&employee_scope=terminated">Final Settlement Due</a>
    </div>

    <div class="card">
        <h2>Recent Employees</h2>
        <table>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joining Date</th>
            </tr>
            @forelse($recentEmployees as $employee)
                <tr>
                    <td><a href="/admin/employees/{{ $employee->id }}">{{ $employee->employee_id }}</a></td>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->roleName() }}</td>
                    <td>{{ $employee->statusLabel() }}</td>
                    <td>{{ $employee->joining_date?->toDateString() }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No employees found.</td></tr>
            @endforelse
        </table>
    </div>

@endsection
