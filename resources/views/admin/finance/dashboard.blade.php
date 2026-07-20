@extends('layouts.admin')

@section('content')
    <h1>Finance</h1>
    <p>Account balances and operating finance summary for NSYS Agency.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Finance Accounts</p><h2>{{ number_format($summary['total_finance_accounts']) }}</h2></div>
        <div class="stat-card"><p>Total Cash</p><h2>BDT {{ number_format($summary['total_cash'], 2) }}</h2></div>
        <div class="stat-card"><p>Total USD Assets</p><h2>USD {{ number_format($summary['total_usd_assets'], 2) }}</h2></div>
        <div class="stat-card"><p>Total BDT Balance</p><h2>BDT {{ number_format($summary['total_bdt_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total USD Balance</p><h2>USD {{ number_format($summary['total_usd_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Salary Paid This Month</p><h2>BDT {{ number_format($summary['salary_paid_this_month'], 2) }}</h2></div>
        <div class="stat-card"><p>Client Payments This Month</p><h2>BDT {{ number_format($summary['client_payments_this_month'], 2) }}</h2></div>
        <div class="stat-card"><p>Upcoming Salary Liability</p><h2>BDT {{ number_format($summary['upcoming_salary_liability'], 2) }}</h2></div>
        <div class="stat-card"><p>Salary Paid Today</p><h2>BDT {{ number_format($summary['salary_paid_today'], 2) }}</h2></div>
        <div class="stat-card"><p>Largest Salary Payment</p><h2>BDT {{ number_format($summary['largest_salary_payment'], 2) }}</h2></div>
    </div>

    <div class="card">
        <a class="btn" href="/admin/finance/accounts">Finance Accounts</a>
        <a class="btn" href="/admin/finance/reports/balance-sheet">Balance Sheet</a>
        <a class="btn" href="/admin/payroll/payment-report">Salary Payment Report</a>
    </div>

    <div class="card">
        <h2>Recent Accounts</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Type</th>
                    <th>Account</th>
                    <th>Provider</th>
                    <th>Currency</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
                @forelse($accounts as $account)
                    <tr>
                        <td>{{ $account->typeLabel() }}</td>
                        <td>{{ $account->account_name }}</td>
                        <td>{{ $account->provider_name ?: '-' }}</td>
                        <td>{{ $account->currency }}</td>
                        <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                        <td>{{ $account->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No finance accounts found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
