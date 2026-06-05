@extends('layouts.admin')

@section('content')
    <h1>Create Employee Login</h1>

    <a class="btn" href="/admin/employees/{{ $employee->id }}">Back to Employee Profile</a>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $employee->name }} ({{ $employee->employee_id }})</h2>
        <p>Admin must manually enter the employee login email and password.</p>
    </div>

    @if ($errors->any())
        <div class="card" style="color:#ef4444;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="/admin/employees/{{ $employee->id }}/create-login">
            @csrf

            <p>
                Name<br>
                <input type="text" name="name" value="{{ old('name', $employee->name) }}" required>
            </p>

            <p>
                Email<br>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </p>

            <p>
                Password<br>
                <input type="password" name="password" required>
            </p>

            <p>
                Confirm Password<br>
                <input type="password" name="password_confirmation" required>
            </p>

            <button class="btn" type="submit">Create Employee Login</button>
        </form>
    </div>
@endsection
