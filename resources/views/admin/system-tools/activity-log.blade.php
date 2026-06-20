@extends('layouts.admin')

@section('content')
    <div>
        <h1>Activity Log</h1>
        <p>Read-only history of important admin actions across NSYS Lab.</p>
    </div>

    <div class="card">
        <form method="GET" action="/admin/activity-log" style="display:grid;grid-template-columns:repeat(6,minmax(130px,1fr));gap:10px;align-items:end;">
            <label>
                Module
                <select name="module">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Action
                <select name="action">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                User
                <select name="user_id">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                From
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label>
                To
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </label>
            <div style="display:flex;gap:10px;align-items:center;">
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/activity-log">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->user?->name ?: 'System' }}</td>
                        <td>{{ $log->role_name ?: '-' }}</td>
                        <td><span class="badge badge-info">{{ $log->module }}</span></td>
                        <td><strong>{{ $log->action }}</strong></td>
                        <td>
                            {{ $log->description }}
                            @if($log->old_value !== null || $log->new_value !== null)
                                <details style="margin-top:6px;">
                                    <summary>View Changes</summary>
                                    <small>Old: {{ json_encode($log->old_value, JSON_UNESCAPED_UNICODE) ?: '-' }}</small><br>
                                    <small>New: {{ json_encode($log->new_value, JSON_UNESCAPED_UNICODE) ?: '-' }}</small>
                                </details>
                            @endif
                        </td>
                        <td>{{ $log->ip_address ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No activity logs found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $logs->links() }}
    </div>
@endsection
