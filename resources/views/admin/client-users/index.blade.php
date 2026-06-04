@extends('layouts.admin')

@section('content')
    <h1>Client Users</h1>

    <a class="btn" href="/admin/client-users/create">Create Client User</a>

    @if(session('success'))
        <div class="card" style="color:green; margin-top:20px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="card" style="color:red; margin-top:20px;">
            {{ session('error') }}
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Linked Client</th>
                <th>Action</th>
            </tr>

            @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
               <td>
    @if($user->status == 'active')
        <span class="badge badge-success">Active</span>
    @else
        <span class="badge badge-danger">Disabled</span>
    @endif
</td>
                <td>
                    @if($user->client)
                        {{ $user->client->company_name }}
                    @else
                        Not Linked
                    @endif
                </td>
                <td>
                    <a href="/admin/client-users/{{ $user->id }}/reset-password">Reset Password</a>

                    <form method="POST" action="/admin/client-users/{{ $user->id }}/toggle-status" style="display:inline;">
                        @csrf
                        <button type="submit">
                            {{ $user->status === 'active' ? 'Disable' : 'Enable' }}
                        </button>
                    </form>

                    <form method="POST" action="/admin/client-users/{{ $user->id }}/delete" style="display:inline;">
                        @csrf
                        <button type="submit" onclick="return confirm('Delete this client user?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
@endsection