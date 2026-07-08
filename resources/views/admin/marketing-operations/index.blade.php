@extends('layouts.admin')

@section('content')
    <h1>Marketing Operations Reports</h1>
    <p>Unified report list across moderator, ad manager, auditor, monitor, trainer, and management operations.</p>

    <div class="card">
        <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
            <select name="report_type"><option value="">All Types</option>@foreach(\App\Models\MarketingOperationsReport::REPORT_TYPES as $value => $label)<option value="{{ $value }}" @selected(($filters['report_type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
            <select name="platform"><option value="">All Platforms</option>@foreach(\App\Models\MarketingOperationsReport::PLATFORMS as $platform)<option value="{{ $platform }}" @selected(($filters['platform'] ?? '') === $platform)>{{ $platform }}</option>@endforeach</select>
            <select name="status"><option value="">All Status</option>@foreach(\App\Models\MarketingOperationsReport::STATUSES as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Date</th><th>Type</th><th>Platform</th><th>Employee</th><th>Client</th><th>Page</th><th>Campaign</th><th>Status</th><th>Action</th></tr>
            @forelse($reports as $report)
                <tr>
                    <td>{{ $report->report_date?->toDateString() }}</td>
                    <td>{{ $report->reportTypeLabel() }}</td>
                    <td>{{ $report->platform }}</td>
                    <td>{{ $report->employee?->name ?: '-' }}</td>
                    <td>{{ $report->client?->company_name ?: '-' }}</td>
                    <td>{{ $report->page?->page_name ?: '-' }}</td>
                    <td>{{ $report->campaign?->campaign_name ?: '-' }}</td>
                    <td><span class="badge badge-info">{{ $report->statusLabel() }}</span></td>
                    <td>
                        <form method="POST" action="/admin/marketing-operations/reports/{{ $report->id }}/status" style="display:flex;gap:6px;flex-wrap:wrap;">
                            @csrf
                            <select name="status">
                                @foreach(['approved' => 'Approve', 'rejected' => 'Reject', 'needs_correction' => 'Needs Correction', 'fixed' => 'Fixed', 'repeated' => 'Repeated', 'closed' => 'Closed'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="admin_note" placeholder="Admin note">
                            <button class="btn" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">No marketing operations reports found.</td></tr>
            @endforelse
        </table>
        {{ $reports->links() }}
    </div>
@endsection
