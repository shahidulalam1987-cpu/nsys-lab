@extends('layouts.employee')
@section('content')
<h1>My Performance</h1><p style="color:var(--muted)">Your approved submission performance only.</p>
@foreach(['today'=>'Today','week'=>'This Week','month'=>'This Month'] as $key=>$label) @php($kpi=$kpis[$key])
<div class="card"><h2>{{ $label }}</h2><div class="stats-grid"><div class="stat-card"><p>Orders Submitted</p><h2>{{ $kpi['total_orders'] }}</h2></div><div class="stat-card"><p>Approved Spend</p><h2>USD {{ number_format($kpi['approved_spend'],2) }}</h2></div><div class="stat-card"><p>Cost Per Order</p><h2>USD {{ number_format($kpi['average_cpo'],2) }}</h2></div><div class="stat-card"><p>Approval Rate</p><h2>{{ number_format($kpi['approval_rate'],2) }}%</h2></div></div></div>
@endforeach
<div class="card"><h2>Bonus Status</h2><table><tr><th>Rule</th><th>Period</th><th>Amount</th><th>Status</th></tr>@forelse($bonusEarnings as $earning)<tr><td>{{ $earning->rule?->name }}</td><td>{{ $earning->period_start?->toDateString() }} - {{ $earning->period_end?->toDateString() }}</td><td>BDT {{ number_format($earning->bonus_amount,2) }}</td><td>{{ ucfirst($earning->status) }}</td></tr>@empty<tr><td colspan="4">No bonus earnings yet.</td></tr>@endforelse</table></div>
@endsection
