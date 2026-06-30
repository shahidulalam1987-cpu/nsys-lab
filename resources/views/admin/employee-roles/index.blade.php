@extends('layouts.admin')

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <div><h1>Roles</h1><p style="color:#94a3b8; margin:4px 0 0;">Manage employee job roles and department links.</p></div>
        @if(auth()->user()->hasPermission('employee_roles.manage'))
            <a class="btn" href="/admin/employee-roles/create">Add Role</a>
        @endif
    </div>

    @if($errors->any())<div class="card" style="color:#ef4444; margin-top:20px;">{{ $errors->first() }}</div>@endif

    <div class="card" style="margin-top:20px; overflow-x:auto;">
        <table>
            <thead><tr><th>Order</th><th>Role</th><th>Department</th><th>Status</th><th>Employees</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->sort_order }}</td>
                        <td><strong>{{ $role->name }}</strong><br><small style="color:#94a3b8;">{{ $role->description ?: $role->slug }}</small></td>
                        <td>{{ $role->department?->name ?: 'All Departments' }}</td>
                        <td><span class="badge {{ $role->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ ucfirst($role->status) }}</span></td>
                        <td>{{ $role->employees_count }}</td>
                        <td style="white-space:nowrap;">
                            @if(auth()->user()->hasPermission('employee_roles.manage'))
                                <a class="btn" href="/admin/employee-roles/{{ $role->id }}/edit">Edit</a>
                                <form method="POST" action="/admin/employee-roles/{{ $role->id }}" style="display:inline" onsubmit="return confirm('Delete this role?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No roles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
