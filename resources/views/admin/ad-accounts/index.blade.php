@extends('layouts.admin')

@section('content')
    <style>
        .account-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .account-actions .btn {
            border-radius: 9px;
            font-size: 12px;
            min-height: 34px;
            padding: 8px 11px;
        }

        .btn-outline {
            background: rgba(255,255,255,.04);
            border: 1px solid var(--line);
            color: var(--text);
        }

        .btn-outline:hover {
            border-color: var(--cyan);
            color: var(--cyan);
        }

        .alert-chip-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>

    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Ad Accounts</h1>
            <p>Manage ad accounts, thresholds, billing dates, and account health.</p>
        </div>
        <a class="btn" href="/admin/ad-accounts/create">Create Ad Account</a>
        <a class="btn" href="/admin/ad-account-ledger">Financial Ledger</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Ad Accounts</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Active Accounts</p><h2>{{ number_format($summary['active']) }}</h2></div>
        <div class="stat-card"><p>Payment Issue</p><h2>{{ number_format($summary['payment_issue']) }}</h2></div>
        <div class="stat-card"><p>Remaining Threshold</p><h2>USD {{ number_format($summary['remaining_threshold'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Balance</p><h2>USD {{ number_format($summary['total_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Billing Alerts</p><h2>{{ number_format($summary['upcoming_billing'] + $summary['overdue_billing']) }}</h2></div>
    </div>

    <div class="card">
        <h2>Alert Summary</h2>
        <div class="alert-chip-row">
            <span class="badge">Total Threshold: USD {{ number_format($summary['total_threshold'], 2) }}</span>
            <span class="badge badge-warning">Near Threshold: {{ number_format($summary['near_threshold']) }}</span>
            <span class="badge badge-danger">At Risk: {{ number_format($summary['at_risk']) }}</span>
            <span class="badge badge-danger">Reached Limit: {{ number_format($summary['limit_reached']) }}</span>
            <span class="badge badge-warning">Upcoming Billing: {{ number_format($summary['upcoming_billing']) }}</span>
            <span class="badge badge-danger">Overdue Billing: {{ number_format($summary['overdue_billing']) }}</span>
            <span class="badge badge-warning">Low Balance: {{ number_format($summary['low_balance']) }}</span>
            <span class="badge badge-danger">Negative Balance: {{ number_format($summary['negative_balance']) }}</span>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/ad-accounts" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <label>BM<br>
                <select name="business_manager_id">
                    <option value="">All BM</option>
                    @foreach($businessManagers as $bm)
                        <option value="{{ $bm->id }}" @selected(($filters['business_manager_id'] ?? '') == $bm->id)>{{ $bm->bm_name }}</option>
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
            <label>Status<br>
                <select name="status">
                    <option value="">All Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Billing Status<br>
                <select name="billing_status">
                    <option value="">All Billing</option>
                    @foreach(['normal' => 'Normal', 'upcoming' => 'Upcoming', 'overdue' => 'Overdue', 'not_set' => 'Not Set'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['billing_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Threshold Status<br>
                <select name="threshold_status">
                    <option value="">All Threshold</option>
                    @foreach(['normal' => 'Normal', 'warning' => 'Warning', 'critical' => 'Critical', 'limit_reached' => 'Limit Reached'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['threshold_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Balance Status<br>
                <select name="balance_status">
                    <option value="">All Balance</option>
                    @foreach(['normal' => 'Normal', 'low' => 'Low Balance', 'negative' => 'Negative Balance'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['balance_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/ad-accounts">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Ad Account</th>
                    <th>BM</th>
                    <th>Client</th>
                    <th>Threshold</th>
                    <th>Current Balance</th>
                    <th>Billing</th>
                    <th>Status</th>
                    <th>Alerts</th>
                    <th>Actions</th>
                </tr>
                @forelse($adAccounts as $account)
                    <tr>
                        <td>
                            <a href="/admin/ad-accounts/{{ $account->id }}" style="font-weight:700;">{{ $account->ad_account_name }}</a>
                            <br><span style="color:var(--muted);">ID: {{ $account->ad_account_id }}</span>
                        </td>
                        <td>{{ $account->businessManager?->bm_name ?: '-' }}</td>
                        <td>{{ $account->client?->company_name ?: '-' }}</td>
                        <td>
                            <strong>{{ $account->currency }} {{ number_format((float) $account->threshold_amount, 2) }}</strong>
                            <br><span style="color:var(--muted);">Used: {{ $account->currency }} {{ number_format((float) $account->current_threshold_usage, 2) }}</span>
                            <br><span style="color:var(--muted);">Remaining: {{ $account->currency }} {{ number_format($account->remaining_threshold, 2) }}</span>
                        </td>
                        <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                        <td>{{ $account->monthly_billing_date ?: '-' }}</td>
                        <td>
                            @php
                                $statusClass = [
                                    'active' => 'badge-success',
                                    'payment_issue' => 'badge-warning',
                                    'review' => 'badge-info',
                                    'disabled' => 'badge-danger',
                                    'limit_reached' => 'badge-danger',
                                ][$account->status] ?? 'badge-neutral';
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $account->statusLabel() }}</span>
                        </td>
                        <td>
                            @if($account->thresholdStatus() !== 'normal')
                                <span class="badge {{ in_array($account->thresholdStatus(), ['critical', 'limit_reached'], true) ? 'badge-danger' : 'badge-warning' }}">{{ $account->thresholdStatusLabel() }}</span>
                            @endif
                            @if(in_array($account->billingStatus(), ['upcoming', 'overdue'], true))
                                <span class="badge {{ $account->billingStatus() === 'overdue' ? 'badge-danger' : 'badge-warning' }}">{{ $account->billingStatusLabel() }}</span>
                            @endif
                            @if($account->balanceStatus() !== 'normal')
                                <span class="badge {{ $account->balanceStatus() === 'negative' ? 'badge-danger' : 'badge-warning' }}">{{ $account->balanceStatusLabel() }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="account-actions">
                            <a class="btn btn-outline" href="/admin/ad-accounts/{{ $account->id }}">View</a>
                            <a class="btn btn-outline" href="/admin/ad-accounts/{{ $account->id }}/edit">Edit</a>
                            <form method="POST" action="/admin/ad-accounts/{{ $account->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this ad account?');">Delete</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No ad accounts found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
