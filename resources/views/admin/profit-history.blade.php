@extends('layouts.admin')

@section('content')
    <style>
        .performance-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .performance-header p {
            max-width: 760px;
        }

        .performance-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .performance-source-note {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .performance-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        .performance-filter-grid label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .performance-filter-grid input,
        .performance-filter-grid select {
            width: 100%;
            min-width: 0;
        }

        .performance-table-name {
            color: #e5f2ff;
            font-weight: 800;
        }

        .performance-empty-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            color: #aebbd2;
        }

        @media (max-width: 1180px) {
            .performance-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .performance-header {
                display: block;
            }

            .performance-actions {
                justify-content: flex-start;
                margin-top: 12px;
            }

            .performance-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="performance-header">
        <div>
            <h1>Performance Reports</h1>
            <p>Focused business performance view for spend, orders, and cost per order.</p>
        </div>
        <div class="performance-actions">
            <a class="btn" href="/admin/export/profit-history">Export CSV</a>
            <a class="btn" href="/admin/daily-reports">Daily Performance</a>
        </div>
    </div>

    <div class="performance-source-note">
        Performance Reports use approved merged Daily Performance records. Legacy daily reports remain available in their original workflow and are not mixed into these totals.
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Spend</p><h2>USD {{ number_format($summary['spend'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Orders</p><h2>{{ number_format($summary['orders']) }}</h2></div>
        <div class="stat-card"><p>Cost Per Order</p><h2>USD {{ number_format($summary['cost_per_order'], 2) }}</h2></div>
    </div>

    <div class="card">
        <h2>Filters</h2>
        <form method="GET" action="/admin/profit-history" class="performance-filter-grid">
            <label>
                From Date
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label>
                To Date
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </label>
            <label>
                Business Manager
                <select name="business_manager_id">
                    <option value="">All Business Managers</option>
                    @foreach($businessManagers as $bm)
                        <option value="{{ $bm->id }}" @selected(($filters['business_manager_id'] ?? '') == $bm->id)>{{ $bm->bm_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Ad Account
                <select name="ad_account_id">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" @selected(($filters['ad_account_id'] ?? '') == $account->id)>{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Client
                <select name="client_id">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Page
                <select name="client_page_id">
                    <option value="">All Pages</option>
                    @foreach($clientPages as $page)
                        <option value="{{ $page->id }}" @selected(($filters['client_page_id'] ?? '') == $page->id)>{{ $page->page_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Campaign
                <select name="campaign_id">
                    <option value="">All Campaigns</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected(($filters['campaign_id'] ?? '') == $campaign->id)>{{ $campaign->campaign_name }}</option>
                    @endforeach
                </select>
            </label>
            <div>
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/profit-history" style="margin-left:12px;">Reset</a>
            </div>
        </form>
    </div>

    @foreach([
        'Client-wise Performance' => $clientRows,
        'Page-wise Performance' => $pageRows,
        'Campaign-wise Performance' => $campaignRows,
        'Business Manager-wise Performance' => $bmRows,
        'Ad Account-wise Performance' => $adAccountRows,
    ] as $title => $rows)
        <div class="card">
            <h2>{{ $title }}</h2>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Spend</th>
                        <th>Orders</th>
                        <th>Cost Per Order</th>
                    </tr>
                    @forelse($rows as $row)
                        <tr>
                            <td><span class="performance-table-name">{{ $row['label'] }}</span></td>
                            <td>USD {{ number_format($row['spend'], 2) }}</td>
                            <td>{{ number_format($row['orders']) }}</td>
                            <td>USD {{ number_format($row['cost_per_order'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="performance-empty-action">
                                    <span>No performance data found for this view.</span>
                                    <a class="btn" href="/admin/daily-reports">Open Daily Performance</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </table>
            </div>
        </div>
    @endforeach
@endsection
