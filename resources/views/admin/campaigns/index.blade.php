@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Campaign Management</h1>
            <p>Manage campaigns across BM, ad account, client, and page relationships.</p>
        </div>
        <a class="btn" href="/admin/campaigns/create">Create Campaign</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Campaigns</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Active Campaigns</p><h2>{{ number_format($summary['active']) }}</h2></div>
        <div class="stat-card"><p>Paused Campaigns</p><h2>{{ number_format($summary['paused']) }}</h2></div>
        <div class="stat-card"><p>Completed Campaigns</p><h2>{{ number_format($summary['completed']) }}</h2></div>
        <div class="stat-card"><p>Archived Campaigns</p><h2>{{ number_format($summary['archived']) }}</h2></div>
        <div class="stat-card"><p>Ending Within 7 Days</p><h2>{{ number_format($summary['ending_soon']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/campaigns" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
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
            <label>Objective<br>
                <select name="objective">
                    <option value="">All Objectives</option>
                    @foreach($objectives as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['objective'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status<br>
                <select name="status">
                    <option value="">All Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>From<br><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
            <label>To<br><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
            <label>Search<br><input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or ID"></label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/campaigns">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Campaign Name</th>
                    <th>Campaign ID</th>
                    <th>Client</th>
                    <th>Page</th>
                    <th>BM</th>
                    <th>Ad Account</th>
                    <th>Objective</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Daily Budget</th>
                    <th>Actions</th>
                </tr>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td><a href="/admin/campaigns/{{ $campaign->id }}">{{ $campaign->campaign_name }}</a></td>
                        <td>{{ $campaign->campaign_id }}</td>
                        <td>{{ $campaign->client?->company_name ?: '-' }}</td>
                        <td>{{ $campaign->page?->page_name ?: '-' }}</td>
                        <td>{{ $campaign->businessManager?->bm_name ?: '-' }}</td>
                        <td>{{ $campaign->adAccount?->ad_account_name ?: '-' }}</td>
                        <td>{{ $campaign->objectiveLabel() }}</td>
                        <td>
                            @php($statusClass = [
                                'draft' => 'badge-neutral',
                                'active' => 'badge-success',
                                'paused' => 'badge-warning',
                                'completed' => 'badge-info',
                                'archived' => 'badge-danger',
                            ][$campaign->status] ?? 'badge-neutral')
                            <span class="badge {{ $statusClass }}">{{ $campaign->statusLabel() }}</span>
                            @if($campaign->isEndingSoon())
                                <span class="badge badge-warning">Ending Soon</span>
                            @endif
                        </td>
                        <td>{{ $campaign->start_date?->toDateString() ?: '-' }}</td>
                        <td>{{ $campaign->end_date?->toDateString() ?: '-' }}</td>
                        <td>USD {{ number_format((float) $campaign->daily_budget, 2) }}</td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/campaigns/{{ $campaign->id }}">View</a> |
                            <a href="/admin/campaigns/{{ $campaign->id }}/edit">Edit</a> |
                            <form method="POST" action="/admin/campaigns/{{ $campaign->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this campaign?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12">No campaigns found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
