@extends('layouts.employee')

@section('content')
    <h1>Employee Dashboard</h1>

    <div class="stats-grid">
        <div class="stat-card"><p>Assigned Client</p><h2>{{ $primaryAssignment?->client?->company_name ?: '-' }}</h2></div>
        <div class="stat-card"><p>Assigned Page</p><h2>{{ $primaryAssignment?->page?->page_name ?: '-' }}</h2></div>
        <div class="stat-card"><p>Current Shift</p><h2>{{ $primaryAssignment?->shift?->name ?: $employee->shift?->name ?: '-' }}</h2></div>
        <div class="stat-card"><p>Today's Work Status</p><h2>{{ $todayWorkStatus?->statusLabel() ?: 'Pending' }}</h2></div>
        <div class="stat-card"><p>This Month Working Days</p><h2>{{ number_format($workStatusSummary['working_days'], 2) }}</h2></div>
        <div class="stat-card"><p>Next Salary Date</p><h2>{{ $employee->nextSalaryDate()?->toDateString() ?: '-' }}</h2></div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Upcoming Salary</p><h2>{{ $employee->salaryStatusLabel() === 'Upcoming' ? 'Yes' : 'No' }}</h2></div>
        <div class="stat-card"><p>Unread Notices</p><h2>{{ number_format($unreadNoticeCount) }}</h2></div>
        <div class="stat-card"><p>Pending Work Status</p><h2>{{ number_format($pendingWorkStatusCount) }}</h2></div>
        <div class="stat-card"><p>Attendance Today</p><h2>{{ $todayAttendance?->statusLabel() ?: 'Not Marked' }}</h2></div>
    </div>

    <div class="card">
        <h2>Current Month Work Summary</h2>
        <div class="stats-grid" style="margin-bottom:0;">
            <div class="stat-card"><p>Working Days</p><h2>{{ number_format($workStatusSummary['working_days'], 2) }}</h2></div>
            <div class="stat-card"><p>Half Days</p><h2>{{ number_format($workStatusSummary['half_days']) }}</h2></div>
            <div class="stat-card"><p>Leave Days</p><h2>{{ number_format($workStatusSummary['leave']) }}</h2></div>
            <div class="stat-card"><p>Client Issue Days</p><h2>{{ number_format($workStatusSummary['client_issue']) }}</h2></div>
            <div class="stat-card"><p>Boosting OFF Days</p><h2>{{ number_format($workStatusSummary['boosting_off']) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Salary Summary</h2>
        <div class="stats-grid" style="margin-bottom:0;">
            <div class="stat-card"><p>Generated Salary</p><h2>BDT {{ number_format($salarySummary['generated_salary'], 2) }}</h2></div>
            <div class="stat-card"><p>Paid Salary</p><h2>BDT {{ number_format($salarySummary['paid_salary'], 2) }}</h2></div>
            <div class="stat-card"><p>Due Salary</p><h2>BDT {{ number_format($salarySummary['due_salary'], 2) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Today Attendance</h2>
        <p>Attendance is for shift monitoring only. Salary is calculated from Work Status records.</p>
        <p><strong>Today's Shift:</strong> {{ $todayAttendance?->shift?->name ?: ($primaryAssignment?->shift?->name ?: $employee->shift?->name ?: '-') }}</p>
        <p><strong>Check In:</strong> {{ $todayAttendance?->check_in_at?->format('h:i A') ?: '-' }}</p>
        <p><strong>Check Out:</strong> {{ $todayAttendance?->check_out_at?->format('h:i A') ?: '-' }}</p>
        <p><strong>Today's Status:</strong> {{ $todayAttendance?->statusLabel() ?: 'Not Marked' }}</p>

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
        <h2>Latest Notices</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Date</th>
                </tr>
                @forelse($latestNotices as $notice)
                    <tr>
                        <td>{{ $notice->title }}</td>
                        <td>{{ $notice->categoryLabel() }}</td>
                        <td>{{ $notice->published_at?->toDateString() ?: $notice->created_at?->toDateString() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No notices found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
