@extends('layouts.employee')

@section('content')
    <h1>My Assignments</h1>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Client</th>
                    <th>Assigned Page</th>
                    <th>Shift</th>
                    <th>Assignment Date</th>
                    <th>Status</th>
                </tr>
                @forelse($employee->assignments->sortByDesc('assigned_from') as $assignment)
                    <tr>
                        <td>{{ $assignment->client?->company_name ?: '-' }}</td>
                        <td>{{ $assignment->page?->page_name ?: '-' }}</td>
                        <td>{{ $assignment->shift?->name ?: '-' }}</td>
                        <td>{{ $assignment->assigned_from?->toDateString() ?: '-' }}</td>
                        <td>{{ $assignment->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No assignment found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
