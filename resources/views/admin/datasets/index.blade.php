@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Pixels & Datasets</h1>
            <p>Manage Meta pixels and datasets after Ad Accounts for tracking and campaign attribution.</p>
        </div>
        <a class="btn" href="/admin/datasets/create">Create Pixel/Dataset</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Active</p><h2>{{ number_format($summary['active']) }}</h2></div>
        <div class="stat-card"><p>Restricted</p><h2>{{ number_format($summary['restricted']) }}</h2></div>
        <div class="stat-card"><p>Website Sources</p><h2>{{ number_format($summary['website']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/datasets" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;align-items:end;">
            <label>BM<br><select name="business_manager_id"><option value="">All BM</option>@foreach($businessManagers as $bm)<option value="{{ $bm->id }}" @selected(($filters['business_manager_id'] ?? '') == $bm->id)>{{ $bm->bm_name }}</option>@endforeach</select></label>
            <label>Ad Account<br><select name="ad_account_id"><option value="">All Ad Accounts</option>@foreach($adAccounts as $account)<option value="{{ $account->id }}" @selected(($filters['ad_account_id'] ?? '') == $account->id)>{{ $account->ad_account_name }}</option>@endforeach</select></label>
            <label>Client<br><select name="client_id"><option value="">All Clients</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>{{ $client->company_name }}</option>@endforeach</select></label>
            <label>Event Source<br><select name="event_source_type"><option value="">All Sources</option>@foreach($eventSourceTypes as $value => $label)<option value="{{ $value }}" @selected(($filters['event_source_type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Status<br><select name="status"><option value="">All Status</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Search<br><input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, ID, domain"></label>
            <div><button class="btn" type="submit">Filter</button> <a href="/admin/datasets">Reset</a></div>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr>
                <th>Pixel / Dataset</th>
                <th>BM</th>
                <th>Ad Account</th>
                <th>Client</th>
                <th>Page</th>
                <th>Source</th>
                <th>Status</th>
                <th>Campaigns</th>
                <th>Actions</th>
            </tr>
            @forelse($datasets as $dataset)
                <tr>
                    <td>
                        <strong>{{ $dataset->dataset_name }}</strong><br>
                        <small style="color:var(--muted);">ID: {{ $dataset->dataset_id }}</small>
                        @if($dataset->domain_url)<br><small><a href="{{ $dataset->domain_url }}" target="_blank">{{ $dataset->domain_url }}</a></small>@endif
                    </td>
                    <td>{{ $dataset->businessManager?->bm_name ?: '-' }}</td>
                    <td>{{ $dataset->adAccount?->ad_account_name ?: '-' }}</td>
                    <td>{{ $dataset->client?->company_name ?: '-' }}</td>
                    <td>{{ $dataset->page?->page_name ?: '-' }}</td>
                    <td>{{ $dataset->eventSourceLabel() }}</td>
                    <td><span class="badge {{ $dataset->status === 'active' ? 'badge-success' : ($dataset->status === 'restricted' ? 'badge-danger' : 'badge-neutral') }}">{{ $dataset->statusLabel() }}</span></td>
                    <td>{{ number_format($dataset->campaigns_count) }}</td>
                    <td style="white-space:nowrap;">
                        <a class="btn" href="/admin/datasets/{{ $dataset->id }}/edit">Edit</a>
                        <form method="POST" action="/admin/datasets/{{ $dataset->id }}/delete" style="display:inline;">
                            @csrf
                            <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this Pixel/Dataset?');">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">No pixels or datasets found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
