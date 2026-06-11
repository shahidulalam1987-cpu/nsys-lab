@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Daily Performance Entry</h1>
            <p>Track campaign performance by Campaign ID across BM, ad account, client, and page.</p>
        </div>
        <a class="btn" href="/admin/daily-reports/create">Add Performance</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Spend</p><h2>USD {{ number_format($summary['spend'], 2) }}</h2></div>
        <div class="stat-card"><p>Messages</p><h2>{{ number_format($summary['messages']) }}</h2></div>
        <div class="stat-card"><p>Results</p><h2>{{ number_format($summary['results']) }}</h2></div>
        <div class="stat-card"><p>Leads</p><h2>{{ number_format($summary['leads']) }}</h2></div>
        <div class="stat-card"><p>Orders</p><h2>{{ number_format($summary['orders']) }}</h2></div>
        <div class="stat-card"><p>CPM</p><h2>USD {{ number_format($summary['cpm'], 2) }}</h2></div>
        <div class="stat-card"><p>CPL</p><h2>USD {{ number_format($summary['cpl'], 2) }}</h2></div>
        <div class="stat-card"><p>CPP</p><h2>USD {{ number_format($summary['cpp'], 2) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/daily-reports" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
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
            <label>Campaign Status<br>
                <select name="campaign_status">
                    <option value="">All Status</option>
                    @foreach($campaignStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['campaign_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/daily-reports">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Campaign Name</th>
                    <th>Campaign ID</th>
                    <th>Client</th>
                    <th>Page</th>
                    <th>Spend</th>
                    <th>Messages</th>
                    <th>Results</th>
                    <th>Leads</th>
                    <th>Orders</th>
                    <th>Reach</th>
                    <th>Clicks</th>
                    <th>CPM</th>
                    <th>CPR</th>
                    <th>CPL</th>
                    <th>CPP</th>
                    <th>CPC</th>
                    <th>Actions</th>
                </tr>
                @forelse($reports as $report)
                    <tr>
                        <td>{{ $report->report_date?->toDateString() }}</td>
                        <td><a href="/admin/campaigns/{{ $report->campaign?->id }}">{{ $report->campaign?->campaign_name ?: '-' }}</a></td>
                        <td>{{ $report->campaign?->campaign_id ?: '-' }}</td>
                        <td>{{ $report->campaign?->client?->company_name ?: '-' }}</td>
                        <td>{{ $report->campaign?->page?->page_name ?: '-' }}</td>
                        <td>USD {{ number_format((float) $report->spend, 2) }}</td>
                        <td>{{ number_format($report->messages) }}</td>
                        <td>{{ number_format($report->results) }}</td>
                        <td>{{ number_format($report->leads) }}</td>
                        <td>{{ number_format($report->orders) }}</td>
                        <td>{{ number_format($report->reach) }}</td>
                        <td>{{ number_format($report->clicks) }}</td>
                        <td>USD {{ number_format((float) $report->cpm, 2) }}</td>
                        <td>USD {{ number_format((float) $report->cpr, 2) }}</td>
                        <td>USD {{ number_format((float) $report->cpl, 2) }}</td>
                        <td>USD {{ number_format((float) $report->cpp, 2) }}</td>
                        <td>USD {{ number_format((float) $report->cpc, 2) }}</td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/daily-reports/{{ $report->id }}">View</a> |
                            <a href="/admin/daily-reports/{{ $report->id }}/edit">Edit</a> |
                            <form method="POST" action="/admin/daily-reports/{{ $report->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this performance report?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="18">No daily performance reports found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
