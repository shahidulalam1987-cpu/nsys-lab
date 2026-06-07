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
            <p>Upcoming Salary</p>
            <h2>BDT {{ number_format($clientFundSummary['upcoming_salary'], 2) }}</h2>
            <p>{{ number_format($clientFundSummary['upcoming_employee_count']) }} Employees</p>
        </div>
        <div class="stat-card">
            <p>Unpaid Salary Due</p>
            <h2>BDT {{ number_format($clientFundSummary['unpaid_salary_due'], 2) }}</h2>
            <p>{{ number_format($clientFundSummary['unpaid_employee_count']) }} Employees</p>
        </div>
        <div class="stat-card">
            <p>Client Fund Available</p>
            <h2>BDT {{ number_format($clientFundSummary['available_balance'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Pending Client Payments</p>
            <h2>BDT {{ number_format($pendingSalaryPayments, 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <a class="btn" href="/admin/employees">Employees</a>
        <a class="btn" href="/admin/salary-payments/create">Receive Client Payment</a>
        <a class="btn" href="/admin/salary-payments">Client Payment History</a>
        <a class="btn" href="/admin/salary-payments/pending">Pending Client Payments</a>
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
                    <td>{{ $employee->statusLabel() }}</td>
                    <td>{{ $employee->joining_date?->toDateString() }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No employees found.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="card">
        <h2>Recent Client Payments</h2>
        <table>
            <tr>
                <th>Client</th>
                <th>Payment Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            @forelse($recentSalaryPayments as $payment)
                <tr>
                    <td>{{ $payment->client?->company_name }}</td>
                    <td>{{ $payment->salary_month?->toDateString() }}</td>
                    <td>BDT {{ number_format($payment->amount, 2) }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td>{{ $payment->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No client payments found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
