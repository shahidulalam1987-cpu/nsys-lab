@extends('layouts.admin')

@section('content')
    <style>
        .campaign-filter-form {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
        }

        .campaign-filter-form input,
        .campaign-filter-form select,
        .campaign-filter-form button,
        .campaign-filter-form a {
            min-height: 40px;
        }

        .campaign-filter-actions {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        .campaign-filter-reset {
            color: var(--muted);
            font-size: 13px;
            text-decoration: none;
        }

        .campaign-filter-reset:hover {
            color: var(--cyan);
        }

        .campaign-cell {
            min-width: 230px;
        }

        .campaign-title {
            color: #f8fbff;
            display: inline-block;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.25;
            text-decoration: none;
        }

        .campaign-title:hover {
            color: var(--cyan);
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .campaign-id-muted {
            color: var(--muted);
            display: block;
            font-size: 12px;
            margin-top: 5px;
        }

        .campaign-compact {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .campaign-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            padding: 7px 10px;
            white-space: nowrap;
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

        .campaign-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            white-space: nowrap;
        }

        .btn.btn-compact {
            border-radius: 9px;
            font-size: 12px;
            min-height: 34px;
            padding: 8px 11px;
        }

        .btn.btn-outline {
            background: rgba(255,255,255,.04);
            border: 1px solid var(--line);
            color: var(--text);
        }

        .btn.btn-outline:hover {
            border-color: var(--cyan);
            color: var(--cyan);
        }

        @media (max-width: 760px) {
            .campaign-filter-actions {
                grid-column: 1 / -1;
            }
        }
    </style>

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
        <form class="campaign-filter-form" method="GET" action="/admin/campaigns">
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
            <label>Search<br><input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Campaign, ID, or page"></label>
            <div class="campaign-filter-actions">
                <button class="btn" type="submit">Filter</button>
                <a class="campaign-filter-reset" href="/admin/campaigns">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Campaign</th>
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
                    @php
                        $bmName = $campaign->businessManager?->bm_name ?: '-';
                        $adAccountName = $campaign->adAccount?->ad_account_name ?: '-';
                        $shortBm = trim(str_replace(' Agency', '', $bmName));
                        $shortAdAccount = trim(str_replace(' Agency', '', $adAccountName));
                        $objectiveClass = 'objective-' . $campaign->objective;
                        $statusClass = 'campaign-status-' . $campaign->status;
                    @endphp
                    <tr>
                        <td class="campaign-cell">
                            <a class="campaign-title" href="/admin/campaigns/{{ $campaign->id }}">{{ $campaign->campaign_name }}</a>
                            <span class="campaign-id-muted">ID: {{ $campaign->campaign_id }}</span>
                        </td>
                        <td>{{ $campaign->client?->company_name ?: '-' }}</td>
                        <td>{{ $campaign->page?->page_name ?: '-' }}</td>
                        <td><div class="campaign-compact" title="{{ $bmName }}">{{ $shortBm }}</div></td>
                        <td><div class="campaign-compact" title="{{ $adAccountName }}">{{ $shortAdAccount }}</div></td>
                        <td><span class="campaign-badge {{ $objectiveClass }}">{{ $campaign->objectiveLabel() }}</span></td>
                        <td>
                            <span class="campaign-badge {{ $statusClass }}">{{ $campaign->statusLabel() }}</span>
                            @if($campaign->isEndingSoon())
                                <span class="campaign-badge campaign-status-paused" style="margin-top:6px;">Ending Soon</span>
                            @endif
                        </td>
                        <td>{{ $campaign->start_date?->toDateString() ?: '-' }}</td>
                        <td>{{ $campaign->end_date?->toDateString() ?: '-' }}</td>
                        <td>USD {{ number_format((float) $campaign->daily_budget, 2) }}</td>
                        <td>
                            <div class="campaign-actions">
                            <a class="btn btn-outline btn-compact" href="/admin/campaigns/{{ $campaign->id }}">View</a>
                            <a class="btn btn-outline btn-compact" href="/admin/campaigns/{{ $campaign->id }}/edit">Edit</a>
                            <form method="POST" action="/admin/campaigns/{{ $campaign->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger btn-compact" type="submit" onclick="return confirm('Delete this campaign?');">Delete</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11">No campaigns found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
