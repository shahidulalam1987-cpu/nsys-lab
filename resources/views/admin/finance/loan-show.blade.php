@extends('layouts.admin')

@section('content')
    <h1>Loan Details</h1>
    <p>{{ $loan->typeLabel() }} for {{ $loan->person_company_name }}.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Loan Amount</p><h2>BDT {{ number_format((float) $loan->amount, 2) }}</h2></div>
        <div class="stat-card"><p>Paid Amount</p><h2>BDT {{ number_format((float) $loan->paid_amount, 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Balance</p><h2>BDT {{ number_format((float) $loan->remaining_balance, 2) }}</h2></div>
        <div class="stat-card"><p>Status</p><h2>{{ $loan->statusLabel() }}</h2></div>
    </div>

    <div class="card">
        <h2>Loan Information</h2>
        <p><strong>Loan Type:</strong> {{ $loan->typeLabel() }}</p>
        <p><strong>Person / Company:</strong> {{ $loan->person_company_name }}</p>
        <p><strong>Loan Date:</strong> {{ $loan->loan_date?->toDateString() }}</p>
        <p><strong>Due Date:</strong> {{ $loan->due_date?->toDateString() ?: '-' }}</p>
        <p><strong>Note:</strong> {{ $loan->note ?: '-' }}</p>
        <a class="btn" href="/admin/finance/loans/{{ $loan->id }}/edit">Edit Loan</a>
        <a class="btn" href="/admin/finance/loans">Back to Loans</a>
    </div>

    <div class="card">
        <h2>Add Repayment</h2>
        <form method="POST" action="/admin/finance/loans/{{ $loan->id }}/repayments" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;align-items:end;">
            @csrf
            <label>Date<br><input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required></label>
            <label>Amount<br><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required></label>
            <label>Method<br><input type="text" name="method" value="{{ old('method') }}"></label>
            <label>Finance Account<br>
                <select name="finance_account_id" required>
                    <option value="">Select BDT Account</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_name }} - BDT {{ number_format((float) $account->current_balance, 2) }}</option>
                    @endforeach
                </select>
            </label>
            <label style="grid-column:1/-1;">Note<br><textarea name="note" rows="2" style="width:100%;">{{ old('note') }}</textarea></label>
            <button class="btn" type="submit">Save Repayment</button>
        </form>
    </div>

    <div class="card">
        <h2>Repayment History</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Note</th>
                </tr>
                @forelse($loan->repayments as $repayment)
                    <tr>
                        <td>{{ $repayment->payment_date?->toDateString() }}</td>
                        <td>BDT {{ number_format((float) $repayment->amount, 2) }}</td>
                        <td>{{ $repayment->method ?: '-' }}</td>
                        <td>{{ $repayment->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No repayments found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
