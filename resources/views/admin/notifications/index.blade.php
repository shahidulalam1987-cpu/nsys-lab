@extends('layouts.admin')

@section('content')
    <h1>Notification Center</h1>
    <p>Central alert history for agency operations, finance, employees, clients, Facebook, and profit monitoring.</p>

    <div class="stats-grid">
        <a class="stat-card" href="/admin/notifications?priority=critical" style="text-decoration:none;border-color:#ef4444;"><p>Critical Alerts</p><h2>{{ number_format($summary['critical']) }}</h2></a>
        <a class="stat-card" href="/admin/notifications?priority=warning" style="text-decoration:none;border-color:#f59e0b;"><p>Warning Alerts</p><h2>{{ number_format($summary['warning']) }}</h2></a>
        <a class="stat-card" href="/admin/notifications?priority=information" style="text-decoration:none;border-color:#2f8cff;"><p>Information Alerts</p><h2>{{ number_format($summary['information']) }}</h2></a>
        <a class="stat-card" href="/admin/notifications?status=unread" style="text-decoration:none;"><p>Unread</p><h2>{{ number_format($summary['unread']) }}</h2></a>
    </div>

    <div class="card">
        <form method="GET" action="/admin/notifications">
            <select name="priority">
                <option value="">All Priorities</option>
                @foreach(\App\Models\SystemNotification::PRIORITIES as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['priority'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="department">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department }}" {{ ($filters['department'] ?? '') === $department ? 'selected' : '' }}>{{ $department }}</option>
                @endforeach
            </select>

            <select name="status">
                <option value="">All Status</option>
                @foreach(\App\Models\SystemNotification::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/notifications">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Department</th>
                    <th>Priority</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                @forelse($notifications as $notification)
                    <tr>
                        <td>
                            {{ $notification->created_at?->format('Y-m-d H:i') }}
                            @if($notification->resolved_at)
                                <br><span style="color:var(--muted);">Resolved: {{ $notification->resolved_at?->format('Y-m-d H:i') }}</span>
                            @endif
                        </td>
                        <td>{{ ucwords($notification->type) }}</td>
                        <td>
                            {{ $notification->department }}
                            <br><span style="color:var(--muted);">{{ $notification->target_team ?: 'Management' }}</span>
                        </td>
                        <td><span class="badge {{ $notification->priorityBadgeClass() }}">{{ $notification->priorityLabel() }}</span></td>
                        <td>
                            <strong>{{ $notification->message }}</strong>
                            @if($notification->reference_type)
                                <br><span style="color:var(--muted);">{{ $notification->reference_type }} #{{ $notification->reference_id ?: '-' }}</span>
                            @endif
                        </td>
                        <td>{{ $notification->statusLabel() }}</td>
                        <td>
                            @if($notification->action_url)
                                <a class="btn" href="{{ $notification->action_url }}">Open</a>
                            @endif
                            @if($notification->status === 'unread')
                                <form method="POST" action="/admin/notifications/{{ $notification->id }}/status" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="status" value="read">
                                    <button type="submit">Read</button>
                                </form>
                            @endif
                            @if($notification->status !== 'resolved')
                                <form method="POST" action="/admin/notifications/{{ $notification->id }}/status" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit">Resolve</button>
                                </form>
                            @endif
                            @if(! in_array($notification->status, ['dismissed', 'resolved'], true))
                                <form method="POST" action="/admin/notifications/{{ $notification->id }}/status" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="status" value="dismissed">
                                    <button type="submit">Dismiss</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No notifications found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $notifications->links() }}
    </div>
@endsection
