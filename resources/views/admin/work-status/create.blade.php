@extends('layouts.admin')

@section('content')
    <h1>Add Work Status</h1>
    <a class="btn" href="/admin/work-status">Back to Work Status</a>

    <div class="card" style="margin-top:20px;">
        <form method="POST" action="/admin/work-status">
            @csrf
            <p>Employee<br>
                <select name="employee_id" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }} ({{ $employee->employee_id }})
                        </option>
                    @endforeach
                </select>
            </p>
            <p>Client<br>
                <select name="client_id">
                    <option value="">No Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </p>
            <p>Date<br><input type="date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}" required></p>
            <p>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ old('status', 'working') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p>Note<br><textarea name="note">{{ old('note') }}</textarea></p>
            <button class="btn" type="submit">Save Work Status</button>
        </form>
    </div>
@endsection
