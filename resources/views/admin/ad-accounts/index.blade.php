@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Ad Account Management</h1>
            <p>Manage ad accounts, thresholds, billing dates, and account health.</p>
        </div>
        <a class="btn" href="/admin/ad-accounts/create">Create Ad Account</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Ad Accounts</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Active Accounts</p><h2>{{ number_format($summary['active']) }}</h2></div>
        <div class="stat-card"><p>Payment Issue</p><h2>{{ number_format($summary['payment_issue']) }}</h2></div>
        <div class="stat-card"><p>Total Threshold</p><h2>BDT {{ number_format($summary['total_threshold'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Balance</p><h2>BDT {{ number_format($summary['total_balance'], 2) }}</h2></div>
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
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/ad-accounts">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Ad Account Name</th>
                    <th>Ad Account ID</th>
                    <th>BM</th>
                    <th>Client</th>
                    <th>Threshold</th>
                    <th>Used</th>
                    <th>Remaining</th>
                    <th>Current Balance</th>
                    <th>Billing Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                @forelse($adAccounts as $account)
                    <tr>
                        <td><a href="/admin/ad-accounts/{{ $account->id }}">{{ $account->ad_account_name }}</a></td>
                        <td>{{ $account->ad_account_id }}</td>
                        <td>{{ $account->businessManager?->bm_name ?: '-' }}</td>
                        <td>{{ $account->client?->company_name ?: '-' }}</td>
                        <td>{{ $account->currency }} {{ number_format((float) $account->threshold_amount, 2) }}</td>
                        <td>{{ $account->currency }} {{ number_format((float) $account->current_threshold_usage, 2) }}</td>
                        <td>{{ $account->currency }} {{ number_format($account->remaining_threshold, 2) }}</td>
                        <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                        <td>{{ $account->monthly_billing_date ?: '-' }}</td>
                        <td>{{ $account->statusLabel() }}</td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/ad-accounts/{{ $account->id }}">View</a> |
                            <a href="/admin/ad-accounts/{{ $account->id }}/edit">Edit</a> |
                            <form method="POST" action="/admin/ad-accounts/{{ $account->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this ad account?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11">No ad accounts found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
