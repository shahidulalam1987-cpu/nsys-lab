@extends('layouts.client')

@section('content')
    <h1>Employee Department Dashboard</h1>

    <p>{{ $client->company_name }} | Salary Month: {{ $fund['month']->format('Y-m') }}</p>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Assigned Employees</p>
            <h2>{{ number_format($assignments->count()) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Salary Required</p>
            <h2>BDT {{ number_format($fund['summary']['total_salary_required'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Current Due</p>
            <h2 style="color:#ef4444;">BDT {{ number_format($fund['summary']['current_due'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Available Balance</p>
            <h2 style="color:#22c55e;">BDT {{ number_format($fund['summary']['available_balance'], 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <a class="btn" href="/client/employees">My Employees</a>
        <a class="btn" href="/client/salary-fund">Salary Fund</a>
        <a class="btn" href="/client/salary-payments">Salary Payments</a>
        <a class="btn" href="/client/salary-payments/create">Submit Salary Payment</a>
    </div>

    <div class="card">
        <h2>Assigned Employees</h2>
        <table>
            <tr>
                <th>Employee</th>
                <th>Role</th>
                <th>Status</th>
                <th>Assigned From</th>
                <th>Assigned To</th>
            </tr>
            @forelse($assignments->take(10) as $assignment)
                <tr>
                    <td>{{ $assignment->employee?->name }}</td>
                    <td>{{ $assignment->employee?->role }}</td>
                    <td>{{ $assignment->employee?->statusLabel() }}</td>
                    <td>{{ $assignment->assigned_from?->toDateString() }}</td>
                    <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No assigned employees found.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="card">
        <h2>Recent Salary Payments</h2>
        <table>
            <tr>
                <th>Salary Month</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            @forelse($recentSalaryPayments as $payment)
                <tr>
                    <td>{{ $payment->salary_month?->format('Y-m') }}</td>
                    <td>BDT {{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td>{{ $payment->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No salary payments found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
