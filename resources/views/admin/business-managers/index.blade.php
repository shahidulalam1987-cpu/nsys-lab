@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Business Managers</h1>
            <p>Manage Meta Business Managers, connected ad accounts, pages, and campaign ownership.</p>
        </div>
        <a class="btn" href="/admin/business-managers/create">Create Business Manager</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total BM</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Verified BM</p><h2>{{ number_format($summary['verified']) }}</h2></div>
        <div class="stat-card"><p>Restricted BM</p><h2>{{ number_format($summary['restricted']) }}</h2></div>
        <div class="stat-card"><p>Disabled BM</p><h2>{{ number_format($summary['disabled']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/business-managers" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <label>Status<br>
                <select name="status">
                    <option value="">All Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Verification<br>
                <select name="verification_status">
                    <option value="">All Verification</option>
                    @foreach($verificationStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['verification_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/business-managers">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Business Manager</th>
                    <th>ID</th>
                    <th>Owner</th>
                    <th>Verification</th>
                    <th>Status</th>
                    <th>Ad Accounts</th>
                    <th>Pages</th>
                    <th>Actions</th>
                </tr>
                @forelse($businessManagers as $bm)
                    <tr>
                        <td><a href="/admin/business-managers/{{ $bm->id }}" style="font-weight:700;">{{ $bm->bm_name }}</a></td>
                        <td>{{ $bm->bm_id }}</td>
                        <td>{{ $bm->owner_name }}<br><span style="color:var(--muted);">{{ $bm->owner_email }}</span></td>
                        <td><span class="badge">{{ $bm->verificationStatusLabel() }}</span></td>
                        <td><span class="badge">{{ $bm->statusLabel() }}</span></td>
                        <td>{{ number_format($bm->ad_accounts_count) }}</td>
                        <td>{{ number_format($bm->pages_count) }}</td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/business-managers/{{ $bm->id }}">View</a> |
                            <a href="/admin/business-managers/{{ $bm->id }}/edit">Edit</a> |
                            <form method="POST" action="/admin/business-managers/{{ $bm->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this BM?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No BM records found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
