@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>{{ $campaign->campaign_name }}</h1>
            <p>Campaign ID: {{ $campaign->campaign_id }}</p>
        </div>
        <div>
            <a class="btn" href="/admin/campaigns">Back to Campaigns</a>
            <a class="btn" href="/admin/campaigns/{{ $campaign->id }}/edit">Edit Campaign</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Daily Budget</p><h2>USD {{ number_format((float) $campaign->daily_budget, 2) }}</h2></div>
        <div class="stat-card"><p>Lifetime Budget</p><h2>USD {{ number_format((float) $campaign->lifetime_budget, 2) }}</h2></div>
        <div class="stat-card"><p>Total Spend</p><h2>USD {{ number_format($campaign->totalSpend(), 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Budget</p><h2>USD {{ number_format($campaign->remainingBudget(), 2) }}</h2></div>
        <div class="stat-card"><p>Budget Utilization</p><h2>{{ number_format($campaign->budgetUtilizationPercent(), 2) }}%</h2></div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
        <div class="card">
            <h2>Campaign Information</h2>
            <p><strong>Name:</strong> {{ $campaign->campaign_name }}</p>
            <p><strong>ID:</strong> {{ $campaign->campaign_id }}</p>
            <p><strong>Objective:</strong> {{ $campaign->objectiveLabel() }}</p>
            <p><strong>Status:</strong> {{ $campaign->statusLabel() }}</p>
            <p><strong>Start Date:</strong> {{ $campaign->start_date?->toDateString() ?: '-' }}</p>
            <p><strong>End Date:</strong> {{ $campaign->end_date?->toDateString() ?: '-' }}</p>
        </div>
        <div class="card">
            <h2>BM Information</h2>
            <p><strong>BM:</strong> {{ $campaign->businessManager?->bm_name ?: '-' }}</p>
            <p><strong>BM ID:</strong> {{ $campaign->businessManager?->bm_id ?: '-' }}</p>
            <p><strong>Owner:</strong> {{ $campaign->businessManager?->owner_name ?: '-' }}</p>
            <p><strong>Status:</strong> {{ $campaign->businessManager?->statusLabel() ?: '-' }}</p>
        </div>
        <div class="card">
            <h2>Ad Account Information</h2>
            <p><strong>Ad Account:</strong> {{ $campaign->adAccount?->ad_account_name ?: '-' }}</p>
            <p><strong>Ad Account ID:</strong> {{ $campaign->adAccount?->ad_account_id ?: '-' }}</p>
            <p><strong>Currency:</strong> USD</p>
            <p><strong>Status:</strong> {{ $campaign->adAccount?->statusLabel() ?: '-' }}</p>
        </div>
        <div class="card">
            <h2>Client Information</h2>
            <p><strong>Client:</strong> {{ $campaign->client?->company_name ?: '-' }}</p>
            <p><strong>Contact:</strong> {{ $campaign->client?->owner_name ?: '-' }}</p>
            <p><strong>Mobile:</strong> {{ $campaign->client?->mobile ?: '-' }}</p>
        </div>
        <div class="card">
            <h2>Page Information</h2>
            <p><strong>Page:</strong> {{ $campaign->page?->page_name ?: '-' }}</p>
            <p><strong>Page ID:</strong> {{ $campaign->page?->page_id ?: '-' }}</p>
            <p><strong>Platform:</strong> {{ $campaign->page?->platform ?: '-' }}</p>
            <p><strong>URL:</strong>
                @if($campaign->page?->page_url)
                    <a href="{{ $campaign->page->page_url }}" target="_blank">{{ $campaign->page->page_url }}</a>
                @else
                    -
                @endif
            </p>
        </div>
        <div class="card">
            <h2>Status Timeline</h2>
            <p><strong>Created:</strong> {{ $campaign->created_at?->format('Y-m-d h:i A') }}</p>
            <p><strong>Last Updated:</strong> {{ $campaign->updated_at?->format('Y-m-d h:i A') }}</p>
            <p><strong>Notes:</strong> {{ $campaign->notes ?: '-' }}</p>
        </div>
    </div>

    <div class="card">
        <h2>Future Performance Section</h2>
        <p>Daily performance, analytics, Meta API data, pixel tracking, and conversion tracking will connect here in future phases.</p>
    </div>
@endsection
