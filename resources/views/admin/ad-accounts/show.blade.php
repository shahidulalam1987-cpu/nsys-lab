@extends('layouts.admin')

@section('content')
    <h1>Ad Account Details</h1>
    <a class="btn" href="/admin/ad-accounts">Back to Ad Accounts</a>
    <a class="btn" href="/admin/ad-accounts/{{ $adAccount->id }}/edit">Edit Ad Account</a>
    <a class="btn" href="/admin/ad-account-ledger?ad_account_id={{ $adAccount->id }}">Financial Ledger</a>

    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card"><p>Threshold Amount</p><h2>{{ $adAccount->currency }} {{ number_format((float) $adAccount->threshold_amount, 2) }}</h2></div>
        <div class="stat-card"><p>Current Usage</p><h2>{{ $adAccount->currency }} {{ number_format((float) $adAccount->current_threshold_usage, 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Threshold</p><h2>{{ $adAccount->currency }} {{ number_format($adAccount->remaining_threshold, 2) }}</h2></div>
        <div class="stat-card"><p>Current Balance</p><h2>{{ $adAccount->currency }} {{ number_format((float) $adAccount->current_balance, 2) }}</h2></div>
        <div class="stat-card"><p>Monthly Billing Date</p><h2>{{ $adAccount->monthly_billing_date ?: '-' }}</h2></div>
        <div class="stat-card"><p>Last Payment Date</p><h2>{{ $adAccount->last_payment_date?->toDateString() ?: '-' }}</h2></div>
        <div class="stat-card"><p>Days Until Billing</p><h2>{{ $adAccount->daysUntilBilling() ?? '-' }}</h2></div>
        <div class="stat-card"><p>Today Spend</p><h2>USD {{ number_format($performanceSummary['today_spend'], 2) }}</h2></div>
        <div class="stat-card"><p>Month Spend</p><h2>USD {{ number_format($performanceSummary['month_spend'], 2) }}</h2></div>
        <div class="stat-card"><p>Campaign Count</p><h2>{{ number_format($performanceSummary['campaign_count']) }}</h2></div>
    </div>

    <div class="card">
        <h2>Financial Alerts</h2>
        <p><strong>Threshold:</strong> {{ $adAccount->thresholdStatusLabel() }} ({{ number_format($adAccount->thresholdUsagePercent(), 2) }}%)</p>
        <p><strong>Billing:</strong> {{ $adAccount->billingStatusLabel() }}</p>
        <p><strong>Balance:</strong> {{ $adAccount->balanceStatusLabel() }}</p>
    </div>

    <div class="card">
        <h2>{{ $adAccount->ad_account_name }}</h2>
        <p><strong>Ad Account ID:</strong> {{ $adAccount->ad_account_id }}</p>
        <p><strong>Currency:</strong> USD</p>
        <p><strong>BM:</strong> {{ $adAccount->businessManager?->bm_name ?: '-' }}</p>
        <p><strong>Client:</strong> {{ $adAccount->client?->company_name ?: '-' }}</p>
        <p><strong>Billing Date:</strong> {{ $adAccount->monthly_billing_date ?: '-' }}</p>
        <p><strong>Last Payment Date:</strong> {{ $adAccount->last_payment_date?->toDateString() ?: '-' }}</p>
        <p><strong>Payment Method:</strong> {{ $adAccount->payment_method ?: '-' }}</p>
        <p><strong>Card Last 4:</strong> {{ $adAccount->card_last_four ?: '-' }}</p>
        <p><strong>Status:</strong> {{ $adAccount->statusLabel() }}</p>
        <p><strong>Notes:</strong> {{ $adAccount->notes ?: '-' }}</p>
    </div>

    <div class="card">
        <h2>Linked Pages</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Page</th><th>Client</th><th>Platform</th><th>Status</th></tr>
                @forelse($adAccount->pages as $page)
                    <tr>
                        <td>
                            @if($page->page_url)
                                <a href="{{ $page->page_url }}" target="_blank" rel="noopener">{{ $page->page_name }}</a>
                            @else
                                {{ $page->page_name }}
                            @endif
                            <br><span style="color:var(--muted);">{{ $page->page_id ?: '-' }}</span>
                        </td>
                        <td>{{ $page->client?->company_name ?: '-' }}</td>
                        <td>{{ $page->platform ?: '-' }}</td>
                        <td>{{ ucfirst((string) $page->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No pages linked.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Linked Campaigns</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Campaign</th><th>Page</th><th>Objective</th><th>Status</th><th>Daily Budget</th></tr>
                @forelse($adAccount->campaigns as $campaign)
                    <tr>
                        <td>
                            <a href="/admin/campaigns/{{ $campaign->id }}">{{ $campaign->campaign_name }}</a>
                            <br><span style="color:var(--muted);">ID: {{ $campaign->campaign_id }}</span>
                        </td>
                        <td>{{ $campaign->page?->page_name ?: '-' }}</td>
                        <td>{{ $campaign->objectiveLabel() }}</td>
                        <td>{{ $campaign->statusLabel() }}</td>
                        <td>USD {{ number_format((float) $campaign->daily_budget, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No campaigns linked.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Recent Financial Ledger</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Transaction Type</th>
                    <th>Amount</th>
                    <th>Previous Value</th>
                    <th>New Value</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
                @forelse($adAccount->ledgers->sortByDesc('transaction_date')->take(10) as $ledger)
                    <tr>
                        <td>{{ $ledger->transaction_date?->toDateString() }}</td>
                        <td>{{ $ledger->typeLabel() }}</td>
                        <td>USD {{ number_format((float) $ledger->amount, 2) }}</td>
                        <td>{{ $ledger->previous_value !== null ? 'USD ' . number_format((float) $ledger->previous_value, 2) : '-' }}</td>
                        <td>{{ $ledger->new_value !== null ? 'USD ' . number_format((float) $ledger->new_value, 2) : '-' }}</td>
                        <td>{{ $ledger->creator?->name ?: '-' }}</td>
                        <td><a href="/admin/ad-account-ledger/{{ $ledger->id }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No ledger records found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
