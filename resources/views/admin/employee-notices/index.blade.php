@extends('layouts.admin')

@section('content')
    <style>
        .notice-filter-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            align-items: end;
        }

        .notice-filter-grid label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .notice-filter-grid input,
        .notice-filter-grid select {
            margin: 6px 0 0;
            width: 100%;
        }

        .notice-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .notice-muted {
            color: var(--muted);
            margin-top: 0;
        }
    </style>

    <h1>Notice Board</h1>
    <p>Publish notices for employee portal users.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Notices</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Salary Notices</p><h2>{{ number_format($summary['salary']) }}</h2></div>
        <div class="stat-card"><p>Emergency Notices</p><h2>{{ number_format($summary['emergency']) }}</h2></div>
        <div class="stat-card"><p>Total Reads</p><h2>{{ number_format($summary['reads']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/employee-notices" class="notice-filter-grid">
            <label>
                Category
                <select name="category">
                    <option value="">All Categories</option>
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['category'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
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
            <label>
                Search
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title or description">
            </label>
            <div class="notice-actions">
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/employee-notices">Reset</a>
                <a class="btn" href="/admin/employee-notices/create">Publish Notice</a>
            </div>
        </form>
    </div>

    <div class="card">
        <p class="notice-muted">Showing {{ number_format($notices->total()) }} notices.</p>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
                @forelse($notices as $notice)
                    <tr>
                        <td>{{ $notice->title }}</td>
                        <td><span class="badge badge-info">{{ $notice->categoryLabel() }}</span></td>
                        <td>{{ $notice->published_at?->toDateString() ?: $notice->created_at?->toDateString() }}</td>
                        <td>
                            {{ \Illuminate\Support\Str::limit($notice->description, 90) }}
                            <br><span style="color:var(--muted);">{{ number_format($notice->reads_count) }} reads</span>
                        </td>
                        <td>
                            <a href="/admin/employee-notices/{{ $notice->id }}/edit">Edit</a>
                            |
                            <form method="POST" action="/admin/employee-notices/{{ $notice->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this employee notice?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No notices found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $notices->links() }}
    </div>
@endsection
