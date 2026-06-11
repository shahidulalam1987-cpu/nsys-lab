@extends('layouts.admin')

@section('content')
    <h1>Performance Details</h1>
    <a class="btn" href="/admin/daily-reports">Back to Daily Performance</a>
    <a class="btn" href="/admin/daily-reports/{{ $dailyReport->id }}/edit">Edit Performance</a>

    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card"><p>Spend</p><h2>USD {{ number_format((float) $dailyReport->spend, 2) }}</h2></div>
        <div class="stat-card"><p>Messages</p><h2>{{ number_format($dailyReport->messages) }}</h2></div>
        <div class="stat-card"><p>Results</p><h2>{{ number_format($dailyReport->results) }}</h2></div>
        <div class="stat-card"><p>Leads</p><h2>{{ number_format($dailyReport->leads) }}</h2></div>
        <div class="stat-card"><p>Orders</p><h2>{{ number_format($dailyReport->orders) }}</h2></div>
        <div class="stat-card"><p>Clicks</p><h2>{{ number_format($dailyReport->clicks) }}</h2></div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
        <div class="card">
            <h2>Campaign Information</h2>
            <p><strong>Campaign:</strong> {{ $dailyReport->campaign?->campaign_name ?: '-' }}</p>
            <p><strong>Campaign ID:</strong> {{ $dailyReport->campaign?->campaign_id ?: '-' }}</p>
            <p><strong>Status:</strong> {{ $dailyReport->campaign?->statusLabel() ?: '-' }}</p>
            <p><strong>Objective:</strong> {{ $dailyReport->campaign?->objectiveLabel() ?: '-' }}</p>
        </div>
        <div class="card">
            <h2>Relationship</h2>
            <p><strong>BM:</strong> {{ $dailyReport->campaign?->businessManager?->bm_name ?: '-' }}</p>
            <p><strong>Ad Account:</strong> {{ $dailyReport->campaign?->adAccount?->ad_account_name ?: '-' }}</p>
            <p><strong>Client:</strong> {{ $dailyReport->campaign?->client?->company_name ?: '-' }}</p>
            <p><strong>Page:</strong> {{ $dailyReport->campaign?->page?->page_name ?: '-' }}</p>
        </div>
        <div class="card">
            <h2>Daily Performance</h2>
            <p><strong>Date:</strong> {{ $dailyReport->report_date?->toDateString() }}</p>
            <p><strong>Reach:</strong> {{ number_format($dailyReport->reach) }}</p>
            <p><strong>Impressions:</strong> {{ number_format($dailyReport->impressions) }}</p>
            <p><strong>Notes:</strong> {{ $dailyReport->notes ?: '-' }}</p>
        </div>
        <div class="card">
            <h2>Calculated Metrics</h2>
            <p><strong>CPM:</strong> USD {{ number_format((float) $dailyReport->cpm, 2) }}</p>
            <p><strong>CPR:</strong> USD {{ number_format((float) $dailyReport->cpr, 2) }}</p>
            <p><strong>CPL:</strong> USD {{ number_format((float) $dailyReport->cpl, 2) }}</p>
            <p><strong>CPP:</strong> USD {{ number_format((float) $dailyReport->cpp, 2) }}</p>
            <p><strong>CPC:</strong> USD {{ number_format((float) $dailyReport->cpc, 2) }}</p>
        </div>
    </div>
@endsection
