@extends('layouts.admin')

@section('content')
    <h1>Finance</h1>
    <p>Account balances, loans, liabilities, and receivables for NSYS Agency.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total BDT Balance</p><h2>BDT {{ number_format($summary['total_bdt_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total USD Balance</p><h2>USD {{ number_format($summary['total_usd_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Loan Taken</p><h2>BDT {{ number_format($summary['total_loan_taken'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Loan Given</p><h2>BDT {{ number_format($summary['total_loan_given'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Remaining Payable</p><h2>BDT {{ number_format($summary['total_remaining_payable'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Remaining Receivable</p><h2>BDT {{ number_format($summary['total_remaining_receivable'], 2) }}</h2></div>
    </div>

    <div class="card">
        <a class="btn" href="/admin/finance/accounts">Finance Accounts</a>
        <a class="btn" href="/admin/finance/loans">Loan Management</a>
        <a class="btn" href="/admin/finance/reports/balance-sheet">Balance Sheet</a>
        <a class="btn" href="/admin/finance/reports/loan-report">Loan Report</a>
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

    <div class="card">
        <h2>Recent Loans</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Type</th>
                    <th>Person / Company</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Status</th>
                </tr>
                @forelse($loans as $loan)
                    <tr>
                        <td>{{ $loan->typeLabel() }}</td>
                        <td><a href="/admin/finance/loans/{{ $loan->id }}">{{ $loan->person_company_name }}</a></td>
                        <td>BDT {{ number_format((float) $loan->amount, 2) }}</td>
                        <td>BDT {{ number_format((float) $loan->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format((float) $loan->remaining_balance, 2) }}</td>
                        <td>{{ $loan->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No loans found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
