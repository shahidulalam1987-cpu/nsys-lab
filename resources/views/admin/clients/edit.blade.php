@extends('layouts.admin')

@section('content')
    <h1>Edit Client</h1>

    <a class="btn" href="/admin/clients/{{ $client->id }}">Back to Client Details</a>
    <a class="btn" href="/admin/clients">Client List</a>

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
        <form method="POST" action="/admin/clients/{{ $client->id }}/update">
            @csrf

            <p>
                Company Name<br>
                <input type="text" name="company_name" value="{{ $client->company_name }}" required>
            </p>

            <p>
                Phone<br>
                <input type="text" name="phone" value="{{ $client->phone }}">
            </p>

            <p>
                Client Rate<br>
                <input type="number" step="0.01" name="client_rate" value="{{ $client->client_rate }}" required>
            </p>

            <p>
                Buy Rate<br>
                <input type="number" step="0.01" name="buy_rate" value="{{ $client->buy_rate }}" required>
            </p>

            <p>
                Status<br>
                <select name="status">
                    <option value="active" {{ $client->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ $client->status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="inactive" {{ $client->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </p>

            <button class="btn" type="submit">Update Client</button>
        </form>
    </div>

    <div class="card">
        <h3>Danger Zone</h3>

        <form method="POST" action="/admin/clients/{{ $client->id }}/delete">
            @csrf

            <button type="submit" onclick="return confirm('Delete this client?')">
                Delete Client
            </button>
        </form>
    </div>
@endsection