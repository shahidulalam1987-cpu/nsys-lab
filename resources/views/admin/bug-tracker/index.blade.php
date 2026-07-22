@extends('layouts.admin')

@section('content')
    <style>
        .bug-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .bug-summary-card {
            display: block;
            min-width: 0;
            text-decoration: none;
        }

        .bug-filter-form {
            display: grid;
            grid-template-columns: minmax(220px, 2fr) repeat(3, minmax(150px, 1fr)) auto;
            gap: 10px;
            align-items: end;
        }

        .bug-filter-form label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .bug-filter-form input,
        .bug-filter-form select {
            width: 100%;
            min-width: 0;
        }

        .bug-filter-actions,
        .bug-row-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .bug-row-actions {
            white-space: nowrap;
        }

        @media (max-width: 980px) {
            .bug-filter-form {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }
    </style>

    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Bug Tracker</h1>
            <p>Internal QA tracker for NSYS admin testing and issue follow-up.</p>
        </div>
        <a class="btn" href="/admin/bug-tracker/create">Add Bug</a>
    </div>

    <div class="card">
        <div class="bug-summary-grid">
            @foreach($statuses as $value => $label)
                <a class="stat-card bug-summary-card" href="/admin/bug-tracker?status={{ $value }}">
                    <p>{{ $label }}</p>
                    <h2>{{ number_format((int) ($statusCounts[$value] ?? 0)) }}</h2>
                </a>
            @endforeach
        </div>

        <form method="GET" action="/admin/bug-tracker" class="bug-filter-form">
            <label>
                Search
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Bug ID, module, title">
            </label>
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
                Priority
                <select name="priority">
                    <option value="">All Priority</option>
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Status
                <select name="status">
                    <option value="">All Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="bug-filter-actions">
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/bug-tracker">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Bug ID</th>
                    <th>Module</th>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Reported By</th>
                    <th>Assigned To</th>
                    <th>Fixed Note</th>
                    <th>Action</th>
                </tr>
                @forelse($bugs as $bug)
                    <tr>
                        <td><strong>{{ $bug->bug_id }}</strong></td>
                        <td>{{ $bug->module }}</td>
                        <td>
                            <strong>{{ $bug->title }}</strong>
                            @if($bug->description)
                                <div style="color:var(--muted);font-size:13px;margin-top:4px;">{{ \Illuminate\Support\Str::limit($bug->description, 90) }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $priorityClass = [
                                    'low' => 'badge-neutral',
                                    'medium' => 'badge-info',
                                    'high' => 'badge-warning',
                                    'critical' => 'badge-danger',
                                ][$bug->priority] ?? 'badge-neutral';
                            @endphp
                            <span class="badge {{ $priorityClass }}">{{ $bug->priorityLabel() }}</span>
                        </td>
                        <td>
                            <form method="POST" action="/admin/bug-tracker/{{ $bug->id }}/status">
                                @csrf
                                <select name="status" onchange="this.form.submit()" style="min-width:130px;">
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($bug->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td>{{ $bug->reported_by ?: '-' }}</td>
                        <td>{{ $bug->assigned_to ?: '-' }}</td>
                        <td>{{ $bug->fixed_note ? \Illuminate\Support\Str::limit($bug->fixed_note, 70) : '-' }}</td>
                        <td>
                            <div class="bug-row-actions">
                                <a class="btn" href="/admin/bug-tracker/{{ $bug->id }}/edit">Edit</a>
                                <form method="POST" action="/admin/bug-tracker/{{ $bug->id }}/delete" style="display:inline;">
                                @csrf
                                    <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this bug record?');">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No bugs found.</td></tr>
                @endforelse
            </table>
        </div>

        {{ $bugs->links() }}
    </div>
@endsection
