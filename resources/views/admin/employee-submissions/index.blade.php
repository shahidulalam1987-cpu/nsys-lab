@extends('layouts.admin')

@section('content')
    <h1>Employee Submissions</h1>
    <p style="color:var(--muted);">Review employee order and spend submissions before merging into Daily Performance.</p>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin:18px 0;">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'merged' => 'Merged'] as $value => $label)
            <a class="btn {{ $status === $value ? 'btn-primary' : '' }}" href="/admin/employee-submissions?status={{ $value }}">{{ $label }}</a>
        @endforeach
        <a class="btn" href="/admin/employee-submissions?status=pending&type=order">Pending Orders</a>
        <a class="btn" href="/admin/employee-submissions?status=pending&type=spend">Pending Spend</a>
    </div>

    @if($errors->any())<div class="card" style="color:#ef4444;">{{ $errors->first() }}</div>@endif

    <div class="card" style="overflow-x:auto;">
        <table>
            <thead><tr><th>Date</th><th>Employee</th><th>Type</th><th>Client / Page</th><th>Campaign</th><th>Performance</th><th>Status</th><th>Note / Proof</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>{{ $submission->submission_date?->toDateString() }}</td>
                        <td>{{ $submission->employee?->employee_id }}<br><strong>{{ $submission->employee?->name }}</strong></td>
                        <td>{{ ucfirst($submission->submission_type) }}</td>
                        <td>{{ $submission->client?->company_name ?: '-' }}<br>{{ $submission->page?->page_name ?: '-' }}</td>
                        <td>{{ $submission->campaign?->campaign_name ?: 'Not Assigned' }}</td>
                        <td>{{ $submission->submission_type === 'order' ? 'Orders: ' . number_format($submission->orders ?? 0) : 'USD ' . number_format($submission->dollar_spend ?? 0, 2) }}</td>
                        <td>
                            @if(data_get($submission->merge_state, 'ready'))
                                <span class="badge badge-warning">Ready to Merge</span>
                            @else
                                <span class="badge">{{ $submission->statusLabel() }}</span>
                            @endif
                        </td>
                        <td>{{ $submission->note ?: '-' }}@if($submission->screenshot_path)<br><a href="{{ \Illuminate\Support\Facades\Storage::url($submission->screenshot_path) }}" target="_blank">View Screenshot</a>@endif</td>
                        <td style="white-space:nowrap;">
                            @if($submission->status !== 'merged')<a class="btn" href="/admin/employee-submissions/{{ $submission->id }}/edit">Edit</a>@endif
                            @if(in_array($submission->status, ['pending', 'rejected']))
                                <form method="POST" action="/admin/employee-submissions/{{ $submission->id }}/approve" style="display:inline">@csrf<button class="btn" type="submit">Approve</button></form>
                            @endif
                            @if($submission->status !== 'merged')
                                <form method="POST" action="/admin/employee-submissions/{{ $submission->id }}/reject" style="display:inline">@csrf<input type="hidden" name="admin_note" value="Correction required"><button class="btn btn-danger" type="submit">Reject</button></form>
                            @endif
                            @if($submission->status === 'approved' && data_get($submission->merge_state, 'ready'))
                                <form method="POST" action="/admin/employee-submissions/{{ $submission->id }}/merge" style="display:inline" @if(data_get($submission->merge_state, 'existing_report_id')) onsubmit="return confirm('A report already exists for this campaign/date. Replace it?')" @endif>
                                    @csrf
                                    @if(data_get($submission->merge_state, 'existing_report_id'))<input type="hidden" name="replace" value="1">@endif
                                    <button class="btn" type="submit">{{ data_get($submission->merge_state, 'existing_report_id') ? 'Replace Report' : 'Merge Report' }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No employee submissions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
