@extends('layouts.admin')

@section('content')
    <h1>Create Client User</h1>

    <a class="btn" href="/admin/client-users">Client Users</a>
    <a class="btn" href="/admin/clients/create">Add Client</a>

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
        <form method="POST" action="/admin/client-users">
            @csrf

            <p>
                Name<br>
                <input type="text" name="name" required>
            </p>

            <p>
                Email<br>
                <input type="email" name="email" required>
            </p>

            <p>
                Password<br>
                <input type="password" name="password" required>
            </p>

            <button class="btn" type="submit">Create Client User</button>
        </form>
    </div>
@endsection