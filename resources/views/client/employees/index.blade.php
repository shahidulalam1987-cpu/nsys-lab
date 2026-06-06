@extends('layouts.client')

@section('content')
    <h1>My Employees</h1>

    <div class="card">
        <table>
            <tr>
                <th>Employee</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joining Date</th>
                <th>Confirmation Date</th>
                <th>Salary</th>
                <th>Salary Status</th>
                <th>Assigned From</th>
                <th>Assigned To</th>
            </tr>
            @forelse($assignments as $assignment)
                @php
                    $latestPayroll = $assignment->employee?->payrolls?->first();
                @endphp
                <tr>
                    <td>{{ $assignment->employee?->name }}</td>
                    <td>{{ $assignment->employee?->role }}</td>
                    <td>{{ $assignment->employee?->statusLabel() }}</td>
                    <td>{{ $assignment->employee?->joining_date?->toDateString() }}</td>
                    <td>{{ $assignment->employee?->confirmation_date?->toDateString() ?: '-' }}</td>
                    <td>BDT {{ number_format($assignment->employee?->monthly_salary ?? 0, 2) }}</td>
                    <td>{{ $latestPayroll ? (['unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$latestPayroll->calculated_status] ?? ucfirst($latestPayroll->calculated_status)) : 'Not Generated' }}</td>
                    <td>{{ $assignment->assigned_from?->toDateString() }}</td>
                    <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No assigned employees found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
