@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Bug Tracker</h1>
            <p>Internal QA tracker for NSYS admin testing and issue follow-up.</p>
        </div>
        <a class="btn" href="/admin/bug-tracker/create">Add Bug</a>
    </div>

    <div class="card">
        <form method="GET" action="/admin/bug-tracker" style="display:grid;grid-template-columns:2fr 1fr 1fr auto auto;gap:10px;align-items:end;">
            <label>
                Search
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Bug ID, module, title">
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
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/bug-tracker">Reset</a>
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
                        <td>{{ $bug->priorityLabel() }}</td>
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
                        <td style="white-space:nowrap;">
                            <a href="/admin/bug-tracker/{{ $bug->id }}/edit">Edit</a>
                            |
                            <form method="POST" action="/admin/bug-tracker/{{ $bug->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this test bug?');">Delete</button>
                            </form>
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
