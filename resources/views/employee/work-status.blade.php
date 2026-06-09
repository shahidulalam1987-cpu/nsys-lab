@extends('layouts.employee')

@section('content')
    <h1>My Work Status</h1>
    <p>Work Status is the official salary calculation source.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Working Days</p><h2>{{ number_format($summary['working_days'], 2) }}</h2></div>
        <div class="stat-card"><p>Half Days</p><h2>{{ number_format($summary['half_days']) }}</h2></div>
        <div class="stat-card"><p>Leave</p><h2>{{ number_format($summary['leave']) }}</h2></div>
        <div class="stat-card"><p>Client Issue</p><h2>{{ number_format($summary['client_issue']) }}</h2></div>
        <div class="stat-card"><p>Boosting OFF</p><h2>{{ number_format($summary['boosting_off']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/employee/work-status">
            <input type="month" name="month" value="{{ $filters['month'] }}">
            <select name="status">
                <option value="">All Status</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
            <a href="/employee/work-status">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Page</th>
                    <th>Shift</th>
                    <th>Status</th>
                    <th>Salary Count Value</th>
                    <th>Note</th>
                </tr>
                @forelse($workStatuses as $workStatus)
                    <tr>
                        <td>{{ $workStatus->work_date?->toDateString() }}</td>
                        <td>{{ $workStatus->client?->company_name ?: '-' }}</td>
                        <td>{{ $workStatus->page?->page_name ?: '-' }}</td>
                        <td>{{ $workStatus->shift?->name ?: '-' }}</td>
                        <td>{{ $workStatus->statusLabel() }}</td>
                        <td>{{ number_format($workStatus->salary_count_value, 2) }}</td>
                        <td>{{ $workStatus->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No work status records found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
