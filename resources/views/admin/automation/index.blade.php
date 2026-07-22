@extends('layouts.admin')

@section('content')
    <style>
        .automation-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 16px;
            max-width: 100%;
        }

        .automation-summary-grid .stat-card {
            min-width: 0;
            text-decoration: none;
        }

        .automation-filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
            align-items: end;
        }

        .automation-filter-form select,
        .automation-filter-form input {
            width: 100%;
            min-width: 0;
        }

        .automation-filter-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 760px) {
            .automation-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <h1>Automation</h1>
    <p>Rule-based workflow tasks, reminders, and department queues. Automation is read-only except task completion.</p>

    <div class="automation-summary-grid">
        <a class="stat-card" href="/admin/automation?status=pending">
            <p>Pending Tasks</p>
            <h2>{{ number_format($summary['pending']) }}</h2>
        </a>
        <a class="stat-card" href="/admin/automation?status=completed" style="border-color:#22c55e;">
            <p>Completed Tasks</p>
            <h2>{{ number_format($summary['completed']) }}</h2>
        </a>
        <a class="stat-card" href="/admin/automation?status=pending&overdue=1" style="border-color:#ef4444;">
            <p>Overdue Tasks</p>
            <h2>{{ number_format($summary['overdue']) }}</h2>
        </a>
        <a class="stat-card" href="/admin/automation?date={{ now()->toDateString() }}" style="border-color:#2f8cff;">
            <p>Created Today</p>
            <h2>{{ number_format($summary['today']) }}</h2>
        </a>
    </div>

    <div class="card">
        <h2>Department Queue</h2>
        <div class="automation-summary-grid">
            @forelse($department_queue as $row)
                <a class="stat-card" href="/admin/automation?department={{ urlencode($row->department_name) }}&status=pending">
                    <p>{{ $row->department_name }}</p>
                    <h2>{{ number_format($row->total) }}</h2>
                </a>
            @empty
                <div class="stat-card"><p>No department queue.</p><h2>0</h2></div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/automation" class="automation-filter-form">
            <select name="department">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department }}" {{ ($filters['department'] ?? '') === $department ? 'selected' : '' }}>{{ $department }}</option>
                @endforeach
            </select>
            <select name="priority">
                <option value="">All Priorities</option>
                @foreach(\App\Models\AutomationTask::PRIORITIES as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['priority'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">All Status</option>
                @foreach(\App\Models\AutomationTask::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="module">
                <option value="">All Modules</option>
                @foreach($modules as $module)
                    <option value="{{ $module }}" {{ ($filters['module'] ?? '') === $module ? 'selected' : '' }}>{{ $module }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}">
            <div class="automation-filter-actions">
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/automation">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Task</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Department</th>
                    <th>Assigned User</th>
                    <th>Related</th>
                    <th>Due Date</th>
                    <th>Action</th>
                </tr>
                @forelse($tasks as $task)
                    <tr>
                        <td>
                            <strong>{{ $task->title }}</strong>
                            <br><span style="color:var(--muted);">{{ $task->task_key }}</span>
                        </td>
                        <td><span class="badge {{ $task->priorityBadgeClass() }}">{{ $task->priorityLabel() }}</span></td>
                        <td>
                            {{ $task->statusLabel() }}
                            @if($task->isOverdue())
                                <br><span class="badge badge-danger">Overdue</span>
                            @endif
                            @if($task->completed_at)
                                <br><span style="color:var(--muted);">{{ $task->completed_at->format('Y-m-d H:i') }}</span>
                            @endif
                        </td>
                        <td>{{ $task->department ?: '-' }}</td>
                        <td>{{ $task->assignedUser?->name ?: '-' }}</td>
                        <td>
                            {{ $task->related_module ?: '-' }}
                            @if($task->related_record_type)
                                <br><span style="color:var(--muted);">{{ class_basename($task->related_record_type) }} #{{ $task->related_record_id ?: '-' }}</span>
                            @endif
                        </td>
                        <td>{{ $task->due_date?->toDateString() ?: '-' }}</td>
                        <td>
                            @if($task->status === 'pending')
                                <form method="POST" action="/admin/automation/tasks/{{ $task->id }}/complete" style="display:inline;">
                                    @csrf
                                    <button class="btn" type="submit">Complete</button>
                                </form>
                            @endif
                            @if($task->related_module)
                                <a class="btn" href="/admin/automation?module={{ urlencode($task->related_module) }}">Filter Module</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No automation tasks found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $tasks->links() }}
    </div>
@endsection
