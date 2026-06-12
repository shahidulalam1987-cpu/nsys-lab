@extends('layouts.admin')

@section('content')
    <h1>Loan Management</h1>
    <p>Track Loan Taken and Loan Given with repayment status.</p>

    <div class="card">
        <h2>Add Loan</h2>
        <form method="POST" action="/admin/finance/loans" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;align-items:end;">
            @csrf
            @include('admin.finance.partials.loan-fields', ['loan' => null])
            <button class="btn" type="submit">Save Loan</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Loan Type</th>
                    <th>Person / Company</th>
                    <th>Amount</th>
                    <th>Loan Date</th>
                    <th>Due Date</th>
                    <th>Paid Amount</th>
                    <th>Remaining Balance</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                @forelse($loans as $loan)
                    <tr>
                        <td>{{ $loan->typeLabel() }}</td>
                        <td><a href="/admin/finance/loans/{{ $loan->id }}">{{ $loan->person_company_name }}</a></td>
                        <td>BDT {{ number_format((float) $loan->amount, 2) }}</td>
                        <td>{{ $loan->loan_date?->toDateString() }}</td>
                        <td>{{ $loan->due_date?->toDateString() ?: '-' }}</td>
                        <td>BDT {{ number_format((float) $loan->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format((float) $loan->remaining_balance, 2) }}</td>
                        <td>{{ $loan->statusLabel() }}</td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/finance/loans/{{ $loan->id }}">View</a>
                            |
                            <a href="/admin/finance/loans/{{ $loan->id }}/edit">Edit</a>
                            <form method="POST" action="/admin/finance/loans/{{ $loan->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this loan record?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No loan records found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
