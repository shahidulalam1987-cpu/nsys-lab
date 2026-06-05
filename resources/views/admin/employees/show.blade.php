@extends('layouts.admin')

@section('content')
    <h1>Employee Profile</h1>

    <a class="btn" href="/admin/employees">Back to Employees</a>
    <a class="btn" href="/admin/employees/{{ $employee->id }}/edit">Edit Employee</a>

    @if(! $employee->user_id)
        <a class="btn" href="/admin/employees/{{ $employee->id }}/create-login">Create Employee Login</a>
    @endif

    @if($employee->isEligibleForConfirmation())
        <form method="POST" action="/admin/employees/{{ $employee->id }}/confirm" style="display:inline;">
            @csrf
            <button class="btn btn-success" type="submit">Confirm Employee</button>
        </form>
    @endif

    <form method="POST" action="/admin/employees/{{ $employee->id }}/terminate" style="display:inline;">
        @csrf
        <button class="btn btn-danger" type="submit" onclick="return confirm('Terminate this employee? History and login will be preserved.');">Deactivate / Terminate</button>
    </form>

    <form method="POST" action="/admin/employees/{{ $employee->id }}/delete" style="display:inline;">
        @csrf
        <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this employee? This is allowed only when no history exists.');">Delete</button>
    </form>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $employee->name }} ({{ $employee->employee_id }})</h2>
        <p><strong>Mobile:</strong> {{ $employee->mobile ?: '-' }}</p>
        <p><strong>Department:</strong> {{ $employee->department }}</p>
        <p><strong>Role:</strong> {{ $employee->role }}</p>
        <p><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $employee->status)) }}</p>
        <p><strong>Joining Date:</strong> {{ $employee->joining_date?->toDateString() }}</p>
        <p><strong>Confirmation Date:</strong> {{ $employee->confirmation_date?->toDateString() ?: '-' }}</p>
        <p><strong>Last Working Date:</strong> {{ $employee->last_working_date?->toDateString() ?: '-' }}</p>
        <p><strong>Monthly Salary:</strong> BDT {{ number_format($employee->monthly_salary, 2) }}</p>
        <p><strong>Bank:</strong> {{ $employee->bank_name ?: '-' }}</p>
        <p><strong>Account:</strong> {{ $employee->account_name ?: '-' }} {{ $employee->account_number ? '(' . $employee->account_number . ')' : '' }}</p>
        <p><strong>Mobile Banking:</strong> {{ $employee->mobile_banking_info ?: '-' }}</p>
        <p><strong>Login Email:</strong> {{ $employee->user?->email ?: 'No login linked' }}</p>
    </div>

    <div class="card">
        <h2>Assign to Client</h2>
        <form method="POST" action="/admin/employees/{{ $employee->id }}/assignments">
            @csrf
            <select name="client_id" required>
                <option value="">Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                @endforeach
            </select>
            <input type="date" name="assigned_from" required>
            <input type="date" name="assigned_to">
            <select name="status" required>
                <option value="active">Active</option>
                <option value="ended">Ended</option>
            </select>
            <input type="text" name="note" placeholder="Note">
            <button class="btn" type="submit">Save Assignment</button>
        </form>
    </div>

    <div class="card">
        <h2>Assignment History</h2>
        <table>
            <tr>
                <th>Client</th>
                <th>From</th>
                <th>To</th>
                <th>Status</th>
                <th>Note</th>
                <th>Update</th>
            </tr>
            @forelse($employee->assignments->sortByDesc('assigned_from') as $assignment)
                <tr>
                    <td>{{ $assignment->client?->company_name }}</td>
                    <td>{{ $assignment->assigned_from?->toDateString() }}</td>
                    <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                    <td>{{ ucfirst($assignment->status) }}</td>
                    <td>{{ $assignment->note ?: '-' }}</td>
                    <td>
                        <form method="POST" action="/admin/employee-assignments/{{ $assignment->id }}/update">
                            @csrf
                            <input type="date" name="assigned_to" value="{{ $assignment->assigned_to?->toDateString() }}">
                            <select name="status">
                                <option value="active" {{ $assignment->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="ended" {{ $assignment->status == 'ended' ? 'selected' : '' }}>Ended</option>
                            </select>
                            <input type="text" name="note" value="{{ $assignment->note }}">
                            <button type="submit">Update</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No assignment history found.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="card">
        <h2>Add Salary Day</h2>
        <form method="POST" action="/admin/employees/{{ $employee->id }}/salary-days">
            @csrf
            <select name="client_id" required>
                <option value="">Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                @endforeach
            </select>
            <input type="date" name="date" required>
            <select name="is_counted" required>
                <option value="1">Counted</option>
                <option value="0">Non-Counted</option>
            </select>
            <select name="reason" required>
                @foreach(\App\Models\SalaryDay::REASONS as $reason)
                    <option value="{{ $reason }}">{{ ucwords(str_replace('_', ' ', $reason)) }}</option>
                @endforeach
            </select>
            <input type="text" name="note" placeholder="Note">
            <button class="btn" type="submit">Save Salary Day</button>
        </form>
    </div>

    <div class="card">
        <h2>Recent Salary Days</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Counted</th>
                <th>Reason</th>
                <th>Note</th>
            </tr>
            @forelse($employee->salaryDays->sortByDesc('date')->take(20) as $day)
                <tr>
                    <td>{{ $day->date?->toDateString() }}</td>
                    <td>{{ $day->client?->company_name }}</td>
                    <td>{{ $day->is_counted ? 'Yes' : 'No' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $day->reason)) }}</td>
                    <td>{{ $day->note ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No salary days found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
