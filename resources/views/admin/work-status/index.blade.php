@extends('layouts.admin')

@section('content')
    <h1>Work Status</h1>
    <p>Daily work status is the official salary calculation source for NSYS Agency.</p>

    <p>
        <a class="btn" href="/admin/work-status/create">Add Work Status</a>
        <a class="btn" href="/admin/work-status/export?{{ http_build_query($filters) }}">Export CSV</a>
    </p>

    @if($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">{{ $errors->first() }}</div>
    @endif

    <div class="stats-grid">
        <div class="stat-card"><p>Salary Count</p><h2>{{ number_format($summary['salary_count'], 2) }}</h2></div>
        <div class="stat-card"><p>Half Days</p><h2>{{ number_format($summary['half_days']) }}</h2></div>
        <div class="stat-card"><p>Leave</p><h2>{{ number_format($summary['leave']) }}</h2></div>
        <div class="stat-card"><p>Client Issue</p><h2>{{ number_format($summary['client_issue']) }}</h2></div>
        <div class="stat-card"><p>Boosting OFF</p><h2>{{ number_format($summary['boosting_off']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/work-status" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end;">
            <select name="employee_id">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ ($filters['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }} ({{ $employee->employee_id }})
                    </option>
                @endforeach
            </select>
            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ ($filters['client_id'] ?? '') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                @endforeach
            </select>
            <select name="client_page_id">
                <option value="">All Pages</option>
                @foreach($clientPages as $page)
                    <option value="{{ $page->id }}" {{ ($filters['client_page_id'] ?? '') == $page->id ? 'selected' : '' }}>{{ $page->page_name }}</option>
                @endforeach
            </select>
            <select name="campaign_id">
                <option value="">All Campaigns</option>
                @foreach($campaigns as $campaign)
                    <option value="{{ $campaign->id }}" {{ ($filters['campaign_id'] ?? '') == $campaign->id ? 'selected' : '' }}>{{ $campaign->campaign_name }}</option>
                @endforeach
            </select>
            <select name="shift_id">
                <option value="">All Shifts</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" {{ ($filters['shift_id'] ?? '') == $shift->id ? 'selected' : '' }}>{{ $shift->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <select name="status">
                <option value="">All Status</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/work-status">Reset</a>
        </form>
        <p style="color:#94a3b8;margin-bottom:0;">Showing {{ number_format($workStatuses->total()) }} matching work status records.</p>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Page</th>
                    <th>Campaign</th>
                    <th>Shift</th>
                    <th>Status</th>
                    <th>Salary Count Value</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
                @forelse($workStatuses as $workStatus)
                    <tr>
                        <td>{{ $workStatus->work_date?->toDateString() }}</td>
                        <td>
                            <a href="/admin/employees/{{ $workStatus->employee?->id }}">{{ $workStatus->employee?->employee_id }}</a><br>
                            {{ $workStatus->employee?->name }}
                        </td>
                        <td>{{ $workStatus->client?->company_name ?: '-' }}</td>
                        <td>{{ $workStatus->page?->page_name ?: '-' }}</td>
                        <td>{{ $workStatus->campaign?->campaign_name ?: '-' }}</td>
                        <td>{{ $workStatus->shift?->name ?: '-' }}</td>
                        <td>{{ $workStatus->statusLabel() }}</td>
                        <td>{{ number_format($workStatus->salary_count_value, 2) }}</td>
                        <td>{{ $workStatus->note ?: '-' }}</td>
                        <td>
                            <a href="/admin/work-status/{{ $workStatus->id }}/edit">Edit</a>
                            |
                            <form method="POST" action="/admin/work-status/{{ $workStatus->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this work status record?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10">No work status records found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $workStatuses->links() }}
    </div>
@endsection
