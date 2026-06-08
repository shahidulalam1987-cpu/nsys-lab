@extends('layouts.admin')

@section('content')
    <h1>Page Management</h1>
    <p>Manage client pages for employee assignment and daily work status.</p>

    <p><a class="btn" href="/admin/client-pages/create">Add Page</a></p>

    <div class="card">
        <form method="GET" action="/admin/client-pages">
            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/client-pages">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Client</th>
                    <th>Page Name</th>
                    <th>Platform</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                @forelse($pages as $page)
                    <tr>
                        <td>{{ $page->client?->company_name ?: '-' }}</td>
                        <td>
                            {{ $page->page_name }}
                            @if($page->page_url)
                                <br><a href="{{ $page->page_url }}" target="_blank">Open Page</a>
                            @endif
                        </td>
                        <td>{{ $page->platform }}</td>
                        <td>{{ ucfirst($page->status) }}</td>
                        <td>
                            <a href="/admin/client-pages/{{ $page->id }}/edit">Edit</a>
                            |
                            <form method="POST" action="/admin/client-pages/{{ $page->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this client page?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No client pages found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
