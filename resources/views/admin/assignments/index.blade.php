@extends('layouts.admin')

@section('content')
    <h1>Assignment Management</h1>
    <p>Assign employees to client pages and shifts for daily operations.</p>

    <p><a class="btn" href="/admin/assignments/create">Create Assignment</a></p>

    <div class="card">
        <h2>Assignment Dashboard</h2>
        <div class="stats-grid" style="margin-bottom:0;">
            <div class="stat-card"><p>Total Assignments</p><h2>{{ number_format($summary['total']) }}</h2></div>
            <div class="stat-card"><p>Active Assignments</p><h2>{{ number_format($summary['active']) }}</h2></div>
            <div class="stat-card"><p>Active Morning Assignments</p><h2>{{ number_format($summary['morning']) }}</h2></div>
            <div class="stat-card"><p>Active Night Assignments</p><h2>{{ number_format($summary['night']) }}</h2></div>
            <div class="stat-card"><p>Active Full Day Assignments</p><h2>{{ number_format($summary['full_day']) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/assignments" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end;">
            <select name="employee_id">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->name }} ({{ $employee->employee_id }})</option>
                @endforeach
            </select>
            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                @endforeach
            </select>
            <select name="client_page_id">
                <option value="">All Pages</option>
                @foreach($clientPages as $page)
                    <option value="{{ $page->id }}" {{ request('client_page_id') == $page->id ? 'selected' : '' }}>{{ $page->page_name }}</option>
                @endforeach
            </select>
            <select name="campaign_id">
                <option value="">All Campaigns</option>
                @foreach($campaigns as $campaign)
                    <option value="{{ $campaign->id }}" {{ request('campaign_id') == $campaign->id ? 'selected' : '' }}>{{ $campaign->campaign_name }}</option>
                @endforeach
            </select>
            <select name="shift_id">
                <option value="">All Shifts</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" {{ request('shift_id') == $shift->id ? 'selected' : '' }}>{{ $shift->name }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Inactive</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Assigned From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" title="Assigned To">
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/assignments">Reset</a>
        </form>
        <p style="color:#94a3b8;margin-bottom:0;">Showing {{ number_format($assignments->total()) }} matching assignments.</p>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Page Name</th>
                    <th>Campaign</th>
                    <th>Shift</th>
                    <th>Assigned From</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                @forelse($assignments as $assignment)
                    <tr>
                        <td>
                            <a href="/admin/employees/{{ $assignment->employee?->id }}">{{ $assignment->employee?->employee_id }}</a><br>
                            {{ $assignment->employee?->name }}
                        </td>
                        <td>{{ $assignment->client?->company_name ?: '-' }}</td>
                        <td>{{ $assignment->page?->page_name ?: '-' }}</td>
                        <td>{{ $assignment->campaignRecord?->campaign_name ?: ($assignment->campaign ?: '-') }}</td>
                        <td>{{ $assignment->shift?->name ?: '-' }}</td>
                        <td>{{ $assignment->assigned_from?->toDateString() ?: '-' }}</td>
                        <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                        <td>{{ $assignment->statusLabel() }}</td>
                        <td>
                            <a href="/admin/assignments/{{ $assignment->id }}">View</a>
                            |
                            <a href="/admin/assignments/{{ $assignment->id }}/edit">Edit</a>
                            |
                            @if($assignment->status === 'active')
                                <form method="POST" action="/admin/assignments/{{ $assignment->id }}/end" style="display:inline;">
                                    @csrf
                                    <button class="btn" type="submit" onclick="return confirm('End this assignment today?');">End</button>
                                </form>
                            @else
                                <form method="POST" action="/admin/assignments/{{ $assignment->id }}/remove" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-danger" type="submit" onclick="return confirm('Remove this inactive assignment?');">Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No assignments found.</td></tr>
                @endforelse
            </table>
        </div>
        <div style="margin-top:14px;">{{ $assignments->links() }}</div>
    </div>
@endsection
