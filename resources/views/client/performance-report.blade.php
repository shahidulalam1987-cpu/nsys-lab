@extends('layouts.client')

@section('content')
    <h1>Performance Reports</h1>
    <p>Modern performance reports approved and merged by NSYS Agency.</p>

    <div class="card">
        <form method="GET" action="/client/performance-reports" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; align-items:end;">
            <label>From Date<input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" style="width:100%; margin:5px 0 0;"></label>
            <label>To Date<input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" style="width:100%; margin:5px 0 0;"></label>
            <label>Page<select name="page_id" style="width:100%; margin:5px 0 0;"><option value="">All Pages</option>@foreach($pages as $page)<option value="{{ $page->id }}" @selected(($filters['page_id'] ?? '') == $page->id)>{{ $page->page_name }}</option>@endforeach</select></label>
            <label>Campaign<select name="campaign_id" style="width:100%; margin:5px 0 0;"><option value="">All Campaigns</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}" @selected(($filters['campaign_id'] ?? '') == $campaign->id)>{{ $campaign->campaign_name }}</option>@endforeach</select></label>
            <div><button class="btn" type="submit">Filter</button> <a href="/client/performance-reports">Reset</a></div>
        </form>
    </div>

    <div class="card table-wrap">
        <h3>Modern Performance Reports</h3>
        <table>
            <thead><tr><th>Date</th><th>Page</th><th>Campaign</th><th>Spend</th><th>Orders</th><th>Cost Per Order</th></tr></thead>
            <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td>{{ $report->report_date?->toDateString() }}</td>
                        <td>{{ $report->campaign?->page?->page_name ?: '-' }}</td>
                        <td>{{ $report->campaign?->campaign_name ?: '-' }}</td>
                        <td>USD {{ number_format($report->spend, 2) }}</td>
                        <td>{{ number_format($report->orders) }}</td>
                        <td>USD {{ number_format($report->cpp, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No modern performance reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
