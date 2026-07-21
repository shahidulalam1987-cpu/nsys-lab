@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Ledger Details</h1>
            <p>Read-only audit record for Ad Account financial activity.</p>
        </div>
        <div>
            <a class="btn" href="/admin/ad-account-ledger">Back to Ledger</a>
            @if($ledger->adAccount)
                <a class="btn" href="/admin/ad-accounts/{{ $ledger->adAccount->id }}">View Ad Account</a>
            @endif
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Transaction Type</p><h2>{{ $ledger->typeLabel() }}</h2></div>
        <div class="stat-card"><p>Amount</p><h2>USD {{ number_format((float) $ledger->amount, 2) }}</h2></div>
        <div class="stat-card"><p>Date</p><h2>{{ $ledger->transaction_date?->toDateString() }}</h2></div>
    </div>

    <div class="card">
        <h2>Account Information</h2>
        <p><strong>Ad Account:</strong> {{ $ledger->adAccount?->ad_account_name ?: '-' }}</p>
        <p><strong>Ad Account ID:</strong> {{ $ledger->adAccount?->ad_account_id ?: '-' }}</p>
        <p><strong>BM:</strong> {{ $ledger->adAccount?->businessManager?->bm_name ?: '-' }}</p>
        <p><strong>Client:</strong> {{ $ledger->adAccount?->client?->company_name ?: '-' }}</p>
    </div>

    <div class="card">
        <h2>Transaction Information</h2>
        <p><strong>Previous Value:</strong> {{ $ledger->previous_value !== null ? 'USD ' . number_format((float) $ledger->previous_value, 2) : '-' }}</p>
        <p><strong>New Value:</strong> {{ $ledger->new_value !== null ? 'USD ' . number_format((float) $ledger->new_value, 2) : '-' }}</p>
        <p><strong>Notes:</strong> {{ $ledger->notes ?: '-' }}</p>
    </div>

    <div class="card">
        <h2>Audit Information</h2>
        <p><strong>Created By:</strong> {{ $ledger->creator?->name ?: '-' }}</p>
        <p><strong>Created At:</strong> {{ $ledger->created_at?->format('Y-m-d h:i A') ?: '-' }}</p>
        <p><strong>Updated At:</strong> {{ $ledger->updated_at?->format('Y-m-d h:i A') ?: '-' }}</p>
    </div>
@endsection
