@extends('layouts.admin')

@section('content')
    <style>
        .activity-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .activity-filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
            align-items: end;
        }

        .activity-filter-form label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .activity-filter-form select,
        .activity-filter-form input {
            width: 100%;
            min-width: 0;
        }

        .activity-filter-actions,
        .activity-quick-modules {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .activity-quick-modules {
            margin-top: 14px;
        }

        .activity-diff-label {
            color: var(--muted);
            display: inline-block;
            font-weight: 800;
            min-width: 70px;
        }
    </style>

    <div>
        <h1>Activity Log</h1>
        <p>Read-only history of important admin actions across NSYS Lab.</p>
    </div>

    <div class="activity-summary-grid">
        <div class="stat-card"><p>Total Logs</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Today</p><h2>{{ number_format($summary['today']) }}</h2></div>
        <div class="stat-card"><p>Modules</p><h2>{{ number_format($summary['modules']) }}</h2></div>
        <div class="stat-card"><p>Users</p><h2>{{ number_format($summary['users']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/activity-log" class="activity-filter-form">
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
            <div class="activity-filter-actions">
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/activity-log">Reset</a>
            </div>
        </form>

        @if($quickModules->isNotEmpty())
            <div class="activity-quick-modules">
                <span style="color:var(--muted);font-weight:800;">Quick Modules</span>
                @foreach($quickModules as $module)
                    <a class="badge {{ ($filters['module'] ?? '') === $module ? 'badge-info' : 'badge-neutral' }}" href="/admin/activity-log?module={{ urlencode($module) }}">{{ $module }}</a>
                @endforeach
            </div>
        @endif
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
                                    <small><span class="activity-diff-label">Previous</span> {{ json_encode($log->old_value, JSON_UNESCAPED_UNICODE) ?: '-' }}</small><br>
                                    <small><span class="activity-diff-label">New</span> {{ json_encode($log->new_value, JSON_UNESCAPED_UNICODE) ?: '-' }}</small>
                                </details>
                            @endif
                        </td>
                        <td>{{ auth()->user()?->isSuperAdmin() ? ($log->ip_address ?: '-') : 'Restricted' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No activity logs found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $logs->links() }}
    </div>
@endsection
