@extends('layouts.employee')

@section('content')
    <h1>My Assignments</h1>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Client</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Status</th>
                    <th>Note</th>
                </tr>
                @forelse($employee->assignments->sortByDesc('assigned_from') as $assignment)
                    <tr>
                        <td>{{ $assignment->client?->company_name ?: '-' }}</td>
                        <td>{{ $assignment->assigned_from?->toDateString() }}</td>
                        <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                        <td>{{ ucfirst($assignment->status) }}</td>
                        <td>{{ $assignment->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No assignment found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
