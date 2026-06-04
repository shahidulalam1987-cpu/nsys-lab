@extends('layouts.admin')

@section('content')
    <h1>Add New Client</h1>

    <a class="btn" href="/admin/clients">Back to Clients</a>
    <a class="btn" href="/admin/client-users/create">Create Client User</a>

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
        <form method="POST" action="/admin/clients">
            @csrf

            <p>
                Login User<br>
                <select name="user_id" required>
                    <option value="">Select Client User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">
                            #{{ $user->id }} - {{ $user->name }} - {{ $user->email }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p>
                Company Name<br>
                <input type="text" name="company_name" required>
            </p>

            <p>
                Phone<br>
                <input type="text" name="phone">
            </p>

            <p>
                Client Rate<br>
                <input type="number" step="0.01" name="client_rate" required>
            </p>

            <p>
                Buy Rate<br>
                <input type="number" step="0.01" name="buy_rate" required>
            </p>

            <p>
                Status<br>
                <select name="status" required>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="inactive">Inactive</option>
                </select>
            </p>

            <button class="btn" type="submit">Save Client</button>
        </form>
    </div>
@endsection