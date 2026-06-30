@extends('layouts.employee')

@section('content')
    <h1>My Profile</h1>

    <div class="card">
        <h2>{{ $employee->name }} ({{ $employee->employee_id }})</h2>
        @if($employee->profile_photo)
            <p><img src="{{ \Illuminate\Support\Facades\Storage::url($employee->profile_photo) }}" alt="{{ $employee->name }}" style="width:110px;height:110px;object-fit:cover;border-radius:12px;"></p>
        @endif
        <p><strong>Employee ID:</strong> {{ $employee->employee_id }}</p>
        <p><strong>Name:</strong> {{ $employee->name }}</p>
        <p><strong>Email:</strong> {{ $employee->email ?: auth()->user()->email }}</p>
        <p><strong>Phone:</strong> {{ $employee->mobile ?: '-' }}</p>
        <p><strong>Department:</strong> {{ $employee->departmentName() }}</p>
        <p><strong>Role:</strong> {{ $employee->role }}</p>
        <p><strong>Joining Date:</strong> {{ $employee->joining_date?->toDateString() ?: '-' }}</p>
        <p><strong>Confirmation Date:</strong> {{ $employee->confirmation_date?->toDateString() ?: '-' }}</p>
        <p><strong>Shift:</strong> {{ $primaryAssignment?->shift?->name ?: $employee->shift?->name ?: '-' }}</p>
        <p><strong>Assigned Client:</strong> {{ $primaryAssignment?->client?->company_name ?: '-' }}</p>
        <p><strong>Assigned Page:</strong> {{ $primaryAssignment?->page?->page_name ?: '-' }}</p>
        <p><strong>Bank Information:</strong> {{ $employee->bank_name ?: '-' }} {{ $employee->account_number ? '(' . $employee->account_number . ')' : '' }}</p>
        <p><strong>Preferred Payment Method:</strong> {{ $employee->preferred_payment_method ? ucfirst($employee->preferred_payment_method) : '-' }}</p>
    </div>

    <div class="card">
        <h2>Update Phone</h2>
        <form method="POST" action="/employee/profile">
            @csrf
            <input type="text" name="mobile" value="{{ old('mobile', $employee->mobile) }}" placeholder="Phone">
            <button class="btn" type="submit">Save Phone</button>
        </form>
    </div>

    <div class="card">
        <h2>Change Password</h2>
        <form method="POST" action="/employee/profile/password">
            @csrf
            <input type="password" name="password" placeholder="New Password" required>
            <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
            <button class="btn" type="submit">Update Password</button>
        </form>
    </div>
@endsection
