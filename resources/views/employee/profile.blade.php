@extends('layouts.employee')

@section('content')
    <h1>My Profile</h1>

    <div class="card">
        <h2>{{ $employee->name }} ({{ $employee->employee_id }})</h2>
        <p><strong>Mobile:</strong> {{ $employee->mobile ?: '-' }}</p>
        <p><strong>Email:</strong> {{ $employee->email ?: auth()->user()->email }}</p>
        <p><strong>Department:</strong> {{ $employee->department }}</p>
        <p><strong>Role:</strong> {{ $employee->role }}</p>
        <p><strong>Joining Date:</strong> {{ $employee->joining_date?->toDateString() ?: '-' }}</p>
        <p><strong>Confirmation Date:</strong> {{ $employee->confirmation_date?->toDateString() ?: '-' }}</p>
        <p><strong>Salary Day:</strong> {{ $employee->salaryCycleDay() ?: '-' }}</p>
        <p><strong>Status:</strong> {{ $employee->statusLabel() }}</p>
    </div>
@endsection
