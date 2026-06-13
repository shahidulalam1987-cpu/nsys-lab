@extends('layouts.admin')

@section('content')
    <h1>Salary Payment Report</h1>
    <p>Confirmed salary payments with finance account, reference, and payment date.</p>

    <div class="card">
        <form method="GET" action="/admin/payroll/payment-report" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <label>From<br><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
            <label>To<br><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
            <label>Month<br><input type="month" name="month" value="{{ $filters['month'] ?? '' }}"></label>
            <label>Employee<br>
                <select name="employee_id">
                    <option value="">All Employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? '') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_id }})</option>
                    @endforeach
                </select>
            </label>
            <label>Client<br>
                <select name="client_id">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Finance Account<br>
                <select name="finance_account_id">
                    <option value="">All Accounts</option>
                    @foreach($financeAccounts as $account)
                        <option value="{{ $account->id }}" @selected(($filters['finance_account_id'] ?? '') == $account->id)>{{ $account->account_name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/payroll/payment-report">Reset</a>
            <a class="btn" href="/admin/payroll/payment-report/export/csv?{{ http_build_query($filters) }}">Export CSV</a>
            <a class="btn" href="/admin/payroll/payment-report/export/excel?{{ http_build_query($filters) }}">Export Excel</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Payments</p><h2>{{ number_format($payrolls->count()) }}</h2></div>
        <div class="stat-card"><p>Total Salary Paid</p><h2>BDT {{ number_format((float) $payrolls->sum('paid_amount'), 2) }}</h2></div>
        <div class="stat-card"><p>Largest Salary Payment</p><h2>BDT {{ number_format((float) $payrolls->max('paid_amount'), 2) }}</h2></div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Employee</th>
                    <th>Month</th>
                    <th>Client</th>
                    <th>Salary</th>
                    <th>Payment Date</th>
                    <th>Finance Account</th>
                    <th>Transaction Reference</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                @forelse($payrolls as $payroll)
                    <tr>
                        <td>{{ $payroll->snapshotEmployeeName() }}<br><span style="color:var(--muted);">{{ $payroll->snapshotEmployeeCode() }}</span></td>
                        <td>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>BDT {{ number_format($payroll->snapshotSalaryAmount(), 2) }}</td>
                        <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                        <td>{{ $payroll->finance_account_name ?: ($payroll->financeAccount?->account_name ?: '-') }}</td>
                        <td>{{ $payroll->transaction_id ?: '-' }}</td>
                        <td><span class="badge badge-success">{{ $payroll->payrollStatusLabel() }}</span></td>
                        <td><a href="/admin/payroll/{{ $payroll->id }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9">No confirmed salary payments found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
