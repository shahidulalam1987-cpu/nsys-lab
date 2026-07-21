@extends('layouts.admin')

@section('content')
    <style>
        .campaign-detail-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .campaign-detail-card p {
            margin: 10px 0;
        }

        .campaign-detail-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            padding: 7px 10px;
        }

        .objective-sales { background: rgba(34, 197, 94, .16); color: #86efac; }
        .objective-messages { background: rgba(66, 232, 255, .14); color: #67e8f9; }
        .objective-leads { background: rgba(47, 140, 255, .18); color: #93c5fd; }
        .objective-traffic { background: rgba(168, 85, 247, .18); color: #d8b4fe; }
        .objective-engagement { background: rgba(245, 158, 11, .18); color: #fcd34d; }
        .objective-reach,
        .objective-video_views,
        .objective-app_promotion,
        .objective-custom { background: rgba(148, 163, 184, .18); color: #cbd5e1; }

        .campaign-status-draft { background: rgba(148, 163, 184, .18); color: #cbd5e1; }
        .campaign-status-active { background: rgba(34, 197, 94, .16); color: #86efac; }
        .campaign-status-paused { background: rgba(245, 158, 11, .18); color: #fcd34d; }
        .campaign-status-completed { background: rgba(47, 140, 255, .18); color: #93c5fd; }
        .campaign-status-archived { background: rgba(148, 163, 184, .18); color: #cbd5e1; }
    </style>

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

    <div class="campaign-detail-grid">
        <div class="card campaign-detail-card">
            <h2>Campaign Information</h2>
            <p><strong>Name:</strong> {{ $campaign->campaign_name }}</p>
            <p><strong>Campaign ID:</strong> {{ $campaign->campaign_id }}</p>
            <p><strong>Objective:</strong> <span class="campaign-detail-badge objective-{{ $campaign->objective }}">{{ $campaign->objectiveLabel() }}</span></p>
            <p><strong>Status:</strong> <span class="campaign-detail-badge campaign-status-{{ $campaign->status }}">{{ $campaign->statusLabel() }}</span></p>
            <p><strong>Daily Budget:</strong> USD {{ number_format((float) $campaign->daily_budget, 2) }}</p>
            <p><strong>Start Date:</strong> {{ $campaign->start_date?->toDateString() ?: '-' }}</p>
            <p><strong>End Date:</strong> {{ $campaign->end_date?->toDateString() ?: '-' }}</p>
            <p><strong>Created Date:</strong> {{ $campaign->created_at?->format('Y-m-d h:i A') ?: '-' }}</p>
            <p><strong>Notes:</strong> {{ $campaign->notes ?: '-' }}</p>
        </div>
        <div class="card campaign-detail-card">
            <h2>BM Information</h2>
            <p><strong>BM:</strong> {{ $campaign->businessManager?->bm_name ?: '-' }}</p>
            <p><strong>BM ID:</strong> {{ $campaign->businessManager?->bm_id ?: '-' }}</p>
            <p><strong>Owner:</strong> {{ $campaign->businessManager?->owner_name ?: '-' }}</p>
            <p><strong>Status:</strong> {{ $campaign->businessManager?->statusLabel() ?: '-' }}</p>
        </div>
        <div class="card campaign-detail-card">
            <h2>Ad Account Information</h2>
            <p><strong>Ad Account:</strong> {{ $campaign->adAccount?->ad_account_name ?: '-' }}</p>
            <p><strong>Ad Account ID:</strong> {{ $campaign->adAccount?->ad_account_id ?: '-' }}</p>
            <p><strong>Currency:</strong> USD</p>
            <p><strong>Status:</strong> {{ $campaign->adAccount?->statusLabel() ?: '-' }}</p>
        </div>
        <div class="card campaign-detail-card">
            <h2>Client Information</h2>
            <p><strong>Client:</strong> {{ $campaign->client?->company_name ?: '-' }}</p>
            <p><strong>Contact:</strong> {{ $campaign->client?->owner_name ?: '-' }}</p>
            <p><strong>Mobile:</strong> {{ $campaign->client?->mobile ?: '-' }}</p>
        </div>
        <div class="card campaign-detail-card">
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
        <div class="card campaign-detail-card">
            <h2>Status Timeline</h2>
            <p><strong>Created:</strong> {{ $campaign->created_at?->format('Y-m-d h:i A') }}</p>
            <p><strong>Last Updated:</strong> {{ $campaign->updated_at?->format('Y-m-d h:i A') }}</p>
            <p><strong>Notes:</strong> {{ $campaign->notes ?: '-' }}</p>
        </div>
    </div>

    <div class="card">
        <h2>Performance Summary</h2>
        <div class="stats-grid" style="margin-bottom:0;">
            <div class="stat-card"><p>Total Spend</p><h2>USD {{ number_format($performanceSummary['spend'], 2) }}</h2></div>
            <div class="stat-card"><p>Orders</p><h2>{{ number_format($performanceSummary['orders']) }}</h2></div>
            <div class="stat-card"><p>Cost Per Order</p><h2>USD {{ number_format(\App\Models\DailyPerformanceReport::costPer($performanceSummary['spend'], $performanceSummary['orders']), 2) }}</h2></div>
            <div class="stat-card"><p>Performance Records</p><h2>{{ number_format($performanceReports->count()) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Performance History</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Spend</th>
                    <th>Orders</th>
                    <th>Cost Per Order</th>
                    <th>Action</th>
                </tr>
                @forelse($performanceReports as $report)
                    <tr>
                        <td>{{ $report->report_date?->toDateString() }}</td>
                        <td>USD {{ number_format((float) $report->spend, 2) }}</td>
                        <td>{{ number_format($report->orders) }}</td>
                        <td>USD {{ number_format((float) $report->cpp, 2) }}</td>
                        <td><a href="/admin/daily-reports/{{ $report->id }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No performance history found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Future Reports</h2>
        <p>Meta API data, pixel tracking, conversion tracking, and advanced campaign profit reporting can connect here in future phases.</p>
    </div>

@endsection
