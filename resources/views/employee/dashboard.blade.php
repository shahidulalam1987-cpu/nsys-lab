@extends('layouts.employee')

@section('content')
    <h1>Employee Dashboard</h1>

    <div class="card">
        <h2>{{ $employee->name }} ({{ $employee->employee_id }})</h2>
        <p><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $employee->status)) }}</p>
        <p><strong>Joining Date:</strong> {{ $employee->joining_date?->toDateString() }}</p>
        <p><strong>Confirmation Status:</strong> {{ $employee->confirmation_date ? 'Confirmed on ' . $employee->confirmation_date->toDateString() : 'Not Confirmed' }}</p>
        <p><strong>Monthly Salary:</strong> BDT {{ number_format($employee->monthly_salary, 2) }}</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Present Days</p><h2>{{ $countedDays }}</h2></div>
        <div class="stat-card"><p>Non Working Days</p><h2>{{ $nonCountedDays }}</h2></div>
        <div class="stat-card"><p>Salary Status</p><h2>{{ $employee->salaryStatusLabel() }}</h2></div>
        <div class="stat-card"><p>Next Salary Date</p><h2>{{ $employee->nextSalaryDate()?->toDateString() ?: '-' }}</h2></div>
    </div>

    <div class="card">
        <h2>Today Attendance</h2>
        <p><strong>Status:</strong> {{ $todayAttendance?->statusLabel() ?: 'Not Marked' }}</p>
        <p><strong>Check In:</strong> {{ $todayAttendance?->check_in_at?->format('h:i A') ?: '-' }}</p>
        <p><strong>Check Out:</strong> {{ $todayAttendance?->check_out_at?->format('h:i A') ?: '-' }}</p>

        <form method="POST" action="/employee/attendance/check-in" style="display:inline;">
            @csrf
            <button class="btn" type="submit" {{ $todayAttendance?->check_in_at ? 'disabled' : '' }}>Check In</button>
        </form>
        <form method="POST" action="/employee/attendance/check-out" style="display:inline;">
            @csrf
            <button class="btn" type="submit" {{ ! $todayAttendance?->check_in_at || $todayAttendance?->check_out_at ? 'disabled' : '' }}>Check Out</button>
        </form>
    </div>

    <div class="card">
        <h2>My Assignment</h2>
        <table>
            <tr>
                <th>Client</th>
                <th>From</th>
                <th>To</th>
                <th>Status</th>
            </tr>
            @forelse($employee->assignments->sortByDesc('assigned_from') as $assignment)
                <tr>
                    <td>{{ $assignment->client?->company_name }}</td>
                    <td>{{ $assignment->assigned_from?->toDateString() }}</td>
                    <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                    <td>{{ ucfirst($assignment->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No assignment found.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="card">
        <h2>My Salary History</h2>
        <table>
            <tr>
                <th>Salary Period</th>
                <th>Month</th>
                <th>Payable Salary (BDT)</th>
                <th>Paid Salary</th>
                <th>Status</th>
            </tr>
            @forelse($payrolls as $payroll)
                <tr>
                    <td>{{ $payroll->salary_period }}</td>
                    <td>{{ $payroll->salary_month?->format('Y-m') }}</td>
                    <td>BDT {{ number_format($payroll->payable_salary, 2) }}</td>
                    <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                    <td>{{ ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No salary history found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
