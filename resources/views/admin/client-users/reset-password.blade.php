@extends('layouts.admin')

@section('content')
    <h1>Reset Password</h1>

    <a class="btn" href="/admin/client-users">Back to Client Users</a>

    @if ($errors->any())
        <div class="card" style="color:red; margin-top:20px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <h2>{{ $user->name }}</h2>
        <p>{{ $user->email }}</p>

        <form method="POST" action="/admin/client-users/{{ $user->id }}/reset-password">
            @csrf

            <p>
                New Password<br>
                <input type="password" name="password" required>
            </p>

            <button class="btn" type="submit">Update Password</button>
        </form>
    </div>
@endsection