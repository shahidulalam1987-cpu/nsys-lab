@extends('layouts.admin')

@section('content')
    <h1>Reset Employee Password</h1>

    <a class="btn" href="/admin/employees/{{ $employee->id }}">Back to Employee Profile</a>

    @if ($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <h2>{{ $employee->name }}</h2>
        <p>{{ $employee->user?->email }}</p>

        <form method="POST" action="/admin/employees/{{ $employee->id }}/reset-login-password">
            @csrf

            <p>
                New Password<br>
                <input type="password" name="password" required>
            </p>

            <p>
                Confirm Password<br>
                <input type="password" name="password_confirmation" required>
            </p>

            <button class="btn" type="submit">Reset Password</button>
        </form>
    </div>
@endsection
