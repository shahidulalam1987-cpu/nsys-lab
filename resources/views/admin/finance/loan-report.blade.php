@extends('layouts.admin')

@section('content')
    <h1>Loan Report</h1>
    <p>Loan taken, loan given, payable and receivable summary.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Loan Taken</p><h2>BDT {{ number_format($summary['total_loan_taken'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Loan Given</p><h2>BDT {{ number_format($summary['total_loan_given'], 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Payable</p><h2>BDT {{ number_format($summary['total_remaining_payable'], 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Receivable</p><h2>BDT {{ number_format($summary['total_remaining_receivable'], 2) }}</h2></div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Type</th>
                    <th>Person / Company</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Loan Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
                @forelse($loans as $loan)
                    <tr>
                        <td>{{ $loan->typeLabel() }}</td>
                        <td><a href="/admin/finance/loans/{{ $loan->id }}">{{ $loan->person_company_name }}</a></td>
                        <td>BDT {{ number_format((float) $loan->amount, 2) }}</td>
                        <td>BDT {{ number_format((float) $loan->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format((float) $loan->remaining_balance, 2) }}</td>
                        <td>{{ $loan->loan_date?->toDateString() }}</td>
                        <td>{{ $loan->due_date?->toDateString() ?: '-' }}</td>
                        <td>{{ $loan->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8">No loans found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
