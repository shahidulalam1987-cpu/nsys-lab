@extends('layouts.admin')

@section('content')
    <h1>Notice Board</h1>
    <p>Publish notices for employee portal users.</p>

    <p><a class="btn" href="/admin/employee-notices/create">Publish Notice</a></p>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
                @forelse($notices as $notice)
                    <tr>
                        <td>{{ $notice->title }}</td>
                        <td>{{ $notice->categoryLabel() }}</td>
                        <td>{{ $notice->published_at?->toDateString() ?: $notice->created_at?->toDateString() }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($notice->description, 90) }}</td>
                        <td>
                            <a href="/admin/employee-notices/{{ $notice->id }}/edit">Edit</a>
                            |
                            <form method="POST" action="/admin/employee-notices/{{ $notice->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this notice?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No notices found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
