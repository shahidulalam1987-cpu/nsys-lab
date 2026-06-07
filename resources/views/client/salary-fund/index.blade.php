@extends('layouts.client')

@section('content')
    <h1>Salary Fund Summary</h1>

    <div class="card">
        <form method="GET" action="/client/salary-fund">
            <input type="month" name="salary_month" value="{{ request('salary_month', $fund['month']->format('Y-m')) }}">
            <button class="btn" type="submit">View Month</button>
            <a class="btn" href="/client/salary-payments/create">Submit Client Fund Payment</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Salary Required</p><h2>BDT {{ number_format($fund['summary']['total_salary_required'], 2) }}</h2></div>
        <div class="stat-card"><p>Paid to NSYS</p><h2>BDT {{ number_format($fund['summary']['paid_to_nsys'], 2) }}</h2></div>
        <div class="stat-card"><p>Current Due</p><h2 style="color:#ef4444;">BDT {{ number_format($fund['summary']['current_due'], 2) }}</h2></div>
        <div class="stat-card"><p>Available Balance</p><h2 style="color:#22c55e;">BDT {{ number_format($fund['summary']['available_balance'], 2) }}</h2></div>
    </div>

    <div class="card">
        <h3>Employee Salary Calculation</h3>
        <table>
            <tr>
                <th>Employee</th>
                <th>Monthly Salary</th>
                <th>Working Days</th>
                <th>Non Working Days</th>
                <th>Required Salary</th>
            </tr>
            @forelse($fund['employee_rows'] as $row)
                <tr>
                    <td>{{ $row['employee']?->name }}</td>
                    <td>BDT {{ number_format($row['employee']?->monthly_salary ?? 0, 2) }}</td>
                    <td>{{ $row['counted_days'] }}</td>
                    <td>{{ $row['non_counted_days'] }}</td>
                    <td>BDT {{ number_format($row['required_salary'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No salary days found for this month.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
