@extends('layouts.admin')

@section('content')
    <h1>{{ $moduleLabel }}</h1>
    <p>Daily operational workspace for {{ strtolower($moduleLabel) }}.</p>

    <div class="card" style="display:flex;gap:10px;flex-wrap:wrap;">
        <a class="btn" href="/admin/marketing-operations/{{ $module }}/operations/create">Add Report</a>
        <a class="btn" href="/admin/marketing-operations/agency">Agency Operations</a>
        <a class="btn" href="/admin/marketing-operations/reports">Reports</a>
    </div>

    <div class="card">
        <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
            <select name="status"><option value="">All Status</option>@foreach(\App\Services\EnterpriseMarketingOperationsService::STATUS_FLOW as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select>
            <select name="client_id"><option value="">All Clients</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(($filters['client_id'] ?? '') == $client->id)>{{ $client->company_name }}</option>@endforeach</select>
            <select name="page_id"><option value="">All Pages</option>@foreach($pages as $page)<option value="{{ $page->id }}" @selected(($filters['page_id'] ?? '') == $page->id)>{{ $page->page_name }}</option>@endforeach</select>
            <select name="campaign_id"><option value="">All Campaigns</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}" @selected(($filters['campaign_id'] ?? '') == $campaign->id)>{{ $campaign->campaign_name }}</option>@endforeach</select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr>
                <th>Date</th><th>Client</th><th>Page</th><th>Campaign</th><th>Employee</th><th>Key Metrics</th><th>Status</th><th>Action</th>
            </tr>
            @forelse($reports as $report)
                <tr>
                    <td>{{ $report->{$dateColumn}?->toDateString() }}</td>
                    <td>{{ $report->client?->company_name ?: '-' }}</td>
                    <td>{{ $report->page?->page_name ?: '-' }}</td>
                    <td>{{ $report->campaign?->campaign_name ?: '-' }}</td>
                    <td>{{ ($module === 'monitor' ? $report->reporter?->name : $report->employee?->name) ?: '-' }}</td>
                    <td>
                        @if($module === 'moderator')
                            Orders {{ number_format($report->orders) }} - Confirmed {{ number_format($report->confirmed_orders) }} - Returned {{ number_format($report->returned_orders ?? 0) }}
                        @elseif($module === 'ad-manager')
                            Spend ${{ number_format($report->spend_usd, 2) }} - CPO ${{ number_format($report->cpp, 2) }}
                        @elseif($module === 'auditor')
                            QA {{ number_format($report->qa_score, 2) }} - {{ ucfirst($report->overall_status) }}
                        @else
                            {{ ucfirst($report->severity) }} - {{ ucfirst($report->resolution_status) }}
                        @endif
                    </td>
                    <td><span class="badge {{ $report->status === 'approved' ? 'badge-success' : ($report->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ ucwords(str_replace('_', ' ', $report->status)) }}</span></td>
                    <td>
                        @if($canManage && $report->status !== 'approved')
                            <form method="POST" action="/admin/marketing-operations/{{ $module }}/operations/{{ $report->id }}/status" style="display:flex;gap:6px;">
                                @csrf
                                <select name="status">
                                    @foreach(['verified' => 'Verify', 'approved' => 'Approve', 'rejected' => 'Reject'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn" type="submit">Save</button>
                            </form>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">No reports found.</td></tr>
            @endforelse
        </table>
        {{ $reports->links() }}
    </div>
@endsection
