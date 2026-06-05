@extends('layouts.admin')

@section('content')
    <h1>Employee Department Dashboard</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Employees</p>
            <h2>{{ number_format($totalEmployees) }}</h2>
        </div>
        <div class="stat-card">
            <p>Active Employees</p>
            <h2>{{ number_format($activeEmployees) }}</h2>
        </div>
        <div class="stat-card">
            <p>Probation Employees</p>
            <h2>{{ number_format($probationEmployees) }}</h2>
        </div>
        <div class="stat-card">
            <p>Pending Salary Payments</p>
            <h2>BDT {{ number_format($pendingSalaryPayments, 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <a class="btn" href="/admin/employees">Employees</a>
        <a class="btn" href="/admin/salary-payments">Salary Payments</a>
        <a class="btn" href="/admin/salary-payments/pending">Pending Salary Payments</a>
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
                    <td>{{ $employee->role }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $employee->status)) }}</td>
                    <td>{{ $employee->joining_date?->toDateString() }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No employees found.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="card">
        <h2>Recent Salary Payments</h2>
        <table>
            <tr>
                <th>Client</th>
                <th>Salary Month</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            @forelse($recentSalaryPayments as $payment)
                <tr>
                    <td>{{ $payment->client?->company_name }}</td>
                    <td>{{ $payment->salary_month?->format('Y-m') }}</td>
                    <td>BDT {{ number_format($payment->amount, 2) }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td>{{ $payment->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No salary payments found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
