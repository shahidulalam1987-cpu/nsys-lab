@extends('layouts.admin')

@section('content')
    <h1>Agency Operations</h1>
    <p>Daily operational summary across moderator, ad manager, auditor, and monitor workflows.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Today's Orders</p><h2>{{ number_format($summary['today_orders']) }}</h2></div>
        <div class="stat-card"><p>Today's Spend</p><h2>${{ number_format($summary['today_spend'], 2) }}</h2></div>
        <div class="stat-card"><p>Today's Revenue</p><h2>BDT {{ number_format($summary['today_revenue'], 2) }}</h2></div>
        <div class="stat-card"><p>Today's Estimated Profit</p><h2>BDT {{ number_format($summary['today_estimated_profit'], 2) }}</h2></div>
        <div class="stat-card"><p>Pending Reports</p><h2>{{ number_format($summary['pending_reports']) }}</h2></div>
        <div class="stat-card"><p>Pending Verifications</p><h2>{{ number_format($summary['pending_verifications']) }}</h2></div>
        <div class="stat-card"><p>Missing Moderator Reports</p><h2>{{ number_format($summary['missing_moderator_reports']) }}</h2></div>
        <div class="stat-card"><p>Missing Ad Reports</p><h2>{{ number_format($summary['missing_ad_reports']) }}</h2></div>
    </div>

    <div class="card table-wrap">
        <h2>Page Daily Records</h2>
        <table>
            <tr><th>Date</th><th>Client</th><th>Page</th><th>Campaign</th><th>Orders</th><th>Spend</th><th>CPP</th><th>Revenue</th><th>Profit</th><th>Status</th></tr>
            @forelse($summaries as $row)
                <tr>
                    <td>{{ $row->summary_date?->toDateString() }}</td>
                    <td>{{ $row->client?->company_name ?: '-' }}</td>
                    <td>{{ $row->page?->page_name ?: '-' }}</td>
                    <td>{{ $row->campaign?->campaign_name ?: '-' }}</td>
                    <td>{{ number_format($row->orders) }}</td>
                    <td>${{ number_format($row->spend_usd, 2) }}</td>
                    <td>${{ number_format($row->cpp, 2) }}</td>
                    <td>BDT {{ number_format($row->revenue, 2) }}</td>
                    <td>BDT {{ number_format($row->profit, 2) }}</td>
                    <td><span class="badge {{ $row->final_status === 'approved' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($row->final_status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="10">No page daily records found.</td></tr>
            @endforelse
        </table>
        {{ $summaries->links() }}
    </div>
@endsection
