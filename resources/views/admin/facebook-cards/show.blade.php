@extends('layouts.admin')

@section('content')
    <h1>Card Details</h1>
    <a class="btn" href="/admin/facebook-cards">Back to Card Balance</a>
    <a class="btn" href="/admin/facebook-cards/{{ $card->id }}/edit">Edit Card</a>

    <div class="stats-grid">
        <div class="stat-card"><p>Current Balance</p><h2>USD {{ number_format((float) $card->current_balance, 2) }}</h2></div>
        <div class="stat-card"><p>Status</p><h2>{{ $card->statusLabel() }}</h2></div>
        <div class="stat-card"><p>Currency</p><h2>USD</h2></div>
        <div class="stat-card"><p>Assigned Ad Account</p><h2>{{ $card->adAccount?->ad_account_name ?: '-' }}</h2></div>
    </div>

    <div class="card">
        <h2>{{ $card->card_name }}</h2>
        <p><strong>Card Type:</strong> {{ $card->card_type ?: '-' }}</p>
        <p><strong>Last 4:</strong> {{ $card->card_last_four ?: '-' }}</p>
        <p><strong>Provider:</strong> {{ $card->provider ?: '-' }}</p>
        <p><strong>Assigned Ad Account:</strong> {{ $card->adAccount?->ad_account_name ?: '-' }}</p>
        <p><strong>Status:</strong> <span class="badge {{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span></p>
        <p><strong>Notes:</strong> {{ $card->notes ?: '-' }}</p>
    </div>
@endsection
