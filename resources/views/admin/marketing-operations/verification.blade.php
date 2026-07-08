@extends('layouts.admin')

@section('content')
    <h1>Performance Verification</h1>
    <p>Unified review grouped by date, platform, client, page, and campaign.</p>

    <div class="card">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>

    @forelse($groups as $key => $items)
        <div class="card">
            <h2>{{ $key }}</h2>
            <div class="table-wrap">
                <table>
                    <tr><th>Type</th><th>Employee</th><th>Target</th><th>Status</th><th>Notes</th><th>Action</th></tr>
                    @foreach($items as $report)
                        <tr>
                            <td>{{ $report->reportTypeLabel() }}</td>
                            <td>{{ $report->employee?->name ?: '-' }}</td>
                            <td>{{ $report->targetEmployee?->name ?: '-' }}</td>
                            <td>{{ $report->statusLabel() }}</td>
                            <td>{{ $report->notes ?: '-' }}</td>
                            <td>
                                <form method="POST" action="/admin/marketing-operations/reports/{{ $report->id }}/status" style="display:flex;gap:6px;flex-wrap:wrap;">
                                    @csrf
                                    <select name="status"><option value="approved">Approve</option><option value="rejected">Reject</option><option value="needs_correction">Needs Correction</option><option value="merged">Merge Daily Performance</option></select>
                                    <input type="text" name="admin_note" placeholder="Admin note">
                                    <button class="btn" type="submit">Apply</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @empty
        <div class="card">No verification groups found.</div>
    @endforelse
@endsection
