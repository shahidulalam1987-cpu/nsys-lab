@extends('layouts.admin')

@section('content')
    <h1>Ad Account Details</h1>
    <a class="btn" href="/admin/ad-accounts">Back to Ad Account Management</a>
    <a class="btn" href="/admin/ad-accounts/{{ $adAccount->id }}/edit">Edit Ad Account</a>

    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card"><p>Threshold Amount</p><h2>{{ $adAccount->currency }} {{ number_format((float) $adAccount->threshold_amount, 2) }}</h2></div>
        <div class="stat-card"><p>Current Usage</p><h2>{{ $adAccount->currency }} {{ number_format((float) $adAccount->current_threshold_usage, 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Threshold</p><h2>{{ $adAccount->currency }} {{ number_format($adAccount->remaining_threshold, 2) }}</h2></div>
        <div class="stat-card"><p>Current Balance</p><h2>{{ $adAccount->currency }} {{ number_format((float) $adAccount->current_balance, 2) }}</h2></div>
    </div>

    <div class="card">
        <h2>{{ $adAccount->ad_account_name }}</h2>
        <p><strong>Ad Account ID:</strong> {{ $adAccount->ad_account_id }}</p>
        <p><strong>BM:</strong> {{ $adAccount->businessManager?->bm_name ?: '-' }}</p>
        <p><strong>Client:</strong> {{ $adAccount->client?->company_name ?: '-' }}</p>
        <p><strong>Billing Date:</strong> {{ $adAccount->monthly_billing_date ?: '-' }}</p>
        <p><strong>Last Payment Date:</strong> {{ $adAccount->last_payment_date?->toDateString() ?: '-' }}</p>
        <p><strong>Payment Method:</strong> {{ $adAccount->payment_method ?: '-' }}</p>
        <p><strong>Card Last 4:</strong> {{ $adAccount->card_last_four ?: '-' }}</p>
        <p><strong>Status:</strong> {{ $adAccount->statusLabel() }}</p>
        <p><strong>Notes:</strong> {{ $adAccount->notes ?: '-' }}</p>
    </div>
@endsection
