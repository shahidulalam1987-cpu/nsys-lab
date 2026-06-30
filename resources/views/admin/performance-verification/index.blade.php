@extends('layouts.admin')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px"><div><h1>Performance Verification</h1><p style="color:var(--muted)">Verify matching order and spend submissions before final merge.</p></div><a class="btn" href="/admin/performance-verification/export">Export CSV</a></div>
<form class="card" method="GET" style="display:flex;gap:10px;flex-wrap:wrap"><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"><select name="status"><option value="">All Statuses</option>@foreach(['pending_order','pending_spend','partial_order','partial_spend','ready_to_merge','merged','mismatch','rejected'] as $value)<option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$value)) }}</option>@endforeach</select><button class="btn">Filter</button></form>
@forelse($groups as $group)
<div class="card">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><h2>{{ $group['date']?->toDateString() }} | {{ $group['client']?->company_name }} | {{ $group['page']?->page_name }}</h2><span class="badge">{{ ucwords(str_replace('_',' ',$group['status'])) }}</span></div>
    <p><strong>Campaign:</strong> {{ $group['campaign']?->campaign_name ?: 'Not Assigned' }}</p>
    <div class="stats-grid">
        <div class="stat-card"><p>Moderator</p><h2>{{ $group['order']?->employee?->name ?: '-' }}</h2><small>Orders {{ $group['order']?->orders ?? 0 }} | Confirmed {{ $group['order']?->confirmed_orders ?? 0 }} | Cancelled {{ $group['order']?->cancelled_orders ?? 0 }}</small></div>
        <div class="stat-card"><p>Ad Manager</p><h2>{{ $group['spend']?->employee?->name ?: '-' }}</h2><small>USD {{ number_format($group['spend']?->dollar_spend ?? 0,2) }} | CPM {{ $group['spend']?->cpm ?? 0 }} | CPC {{ $group['spend']?->cpc ?? 0 }} | CTR {{ $group['spend']?->ctr ?? 0 }}</small></div>
        <div class="stat-card"><p>Cost Per Order</p><h2>USD {{ number_format($group['calculation']['cost_per_order'],2) }}</h2><small>BDT Spend {{ number_format($group['calculation']['bdt_spend'],2) }}</small></div>
        <div class="stat-card"><p>Profit</p><h2>BDT {{ number_format($group['calculation']['profit'],2) }}</h2><small>Margin {{ number_format($group['calculation']['profit_margin'],2) }}%</small></div>
    </div>
    <p><strong>Rates:</strong> Client {{ number_format($group['calculation']['client_rate'],2) }} | Buy {{ number_format($group['calculation']['buy_rate'],2) }} &nbsp; <strong>Admin Note:</strong> {{ $group['admin_note'] ?: '-' }}</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        @foreach([$group['order'],$group['spend']] as $submission) @if($submission)
            @if(in_array($submission->status,['pending','rejected']))<form method="POST" action="/admin/employee-submissions/{{ $submission->id }}/approve">@csrf<button class="btn">Approve {{ ucfirst($submission->submission_type) }}</button></form>@endif
            @if($submission->status !== 'merged')<a class="btn" href="/admin/employee-submissions/{{ $submission->id }}/edit">Edit {{ ucfirst($submission->submission_type) }}</a>@endif
        @endif @endforeach
        @if($group['order'])<form method="POST" action="/admin/performance-verification/{{ $group['order']->id }}/mismatch">@csrf<input type="hidden" name="admin_note" value="Order and spend values require correction"><button class="btn btn-danger">Mark Mismatch</button></form>@endif
        @if($group['status']==='ready_to_merge')<form method="POST" action="/admin/employee-submissions/{{ $group['order']->id }}/merge">@csrf<button class="btn">Merge Final Report</button></form>@endif
    </div>
</div>
@empty <div class="card">No performance groups found.</div> @endforelse
@endsection
