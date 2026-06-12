@extends('layouts.admin')

@section('content')
    <h1>Analytics Dashboard</h1>
    <p>Focused boosting performance view for spend, orders, and cost per order.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Spend</p><h2>USD {{ number_format($summary['spend'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Orders</p><h2>{{ number_format($summary['orders']) }}</h2></div>
        <div class="stat-card"><p>Cost Per Order</p><h2>USD {{ number_format($summary['cost_per_order'], 2) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/profit-history" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <label>From<br><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
            <label>To<br><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
            <label>BM<br>
                <select name="business_manager_id">
                    <option value="">All BM</option>
                    @foreach($businessManagers as $bm)
                        <option value="{{ $bm->id }}" @selected(($filters['business_manager_id'] ?? '') == $bm->id)>{{ $bm->bm_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Ad Account<br>
                <select name="ad_account_id">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" @selected(($filters['ad_account_id'] ?? '') == $account->id)>{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Client<br>
                <select name="client_id">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Page<br>
                <select name="client_page_id">
                    <option value="">All Pages</option>
                    @foreach($clientPages as $page)
                        <option value="{{ $page->id }}" @selected(($filters['client_page_id'] ?? '') == $page->id)>{{ $page->page_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Campaign<br>
                <select name="campaign_id">
                    <option value="">All Campaigns</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected(($filters['campaign_id'] ?? '') == $campaign->id)>{{ $campaign->campaign_name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/profit-history">Reset</a>
        </form>
    </div>

    @foreach([
        'Client-wise Performance' => $clientRows,
        'Page-wise Performance' => $pageRows,
        'Campaign-wise Performance' => $campaignRows,
        'BM-wise Performance' => $bmRows,
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
                            <td>{{ $row['label'] }}</td>
                            <td>USD {{ number_format($row['spend'], 2) }}</td>
                            <td>{{ number_format($row['orders']) }}</td>
                            <td>USD {{ number_format($row['cost_per_order'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No performance data found.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    @endforeach
@endsection
