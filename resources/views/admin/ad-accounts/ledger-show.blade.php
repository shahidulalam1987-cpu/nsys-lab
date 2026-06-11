@extends('layouts.admin')

@section('content')
    <h1>Ledger Details</h1>
    <a class="btn" href="/admin/ad-account-ledger">Back to Ledger</a>

    <div class="card" style="margin-top:20px;">
        <p><strong>Date:</strong> {{ $ledger->transaction_date?->toDateString() }}</p>
        <p><strong>Ad Account:</strong> {{ $ledger->adAccount?->ad_account_name ?: '-' }}</p>
        <p><strong>Transaction Type:</strong> {{ $ledger->typeLabel() }}</p>
        <p><strong>Amount:</strong> USD {{ number_format((float) $ledger->amount, 2) }}</p>
        <p><strong>Previous Value:</strong> {{ $ledger->previous_value !== null ? 'USD ' . number_format((float) $ledger->previous_value, 2) : '-' }}</p>
        <p><strong>New Value:</strong> {{ $ledger->new_value !== null ? 'USD ' . number_format((float) $ledger->new_value, 2) : '-' }}</p>
        <p><strong>Created By:</strong> {{ $ledger->creator?->name ?: '-' }}</p>
        <p><strong>Notes:</strong> {{ $ledger->notes ?: '-' }}</p>
    </div>
@endsection
