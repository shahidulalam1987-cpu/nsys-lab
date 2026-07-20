@extends('layouts.admin')

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <div>
            <h1>Departments</h1>
            <p style="color:#94a3b8; margin:4px 0 0;">Manage employee departments and display order.</p>
        </div>
        @if(auth()->user()->hasPermission('departments.manage'))
            <a class="btn" href="/admin/departments/create">Add Department</a>
        @endif
    </div>

    @if($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">{{ $errors->first() }}</div>
    @endif

    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card"><p>Total Departments</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Active</p><h2>{{ number_format($summary['active']) }}</h2></div>
        <div class="stat-card"><p>Inactive</p><h2>{{ number_format($summary['inactive']) }}</h2></div>
        <div class="stat-card"><p>Assigned Employees</p><h2>{{ number_format($summary['assigned_employees']) }}</h2></div>
        <div class="stat-card"><p>Assigned Roles</p><h2>{{ number_format($summary['assigned_roles']) }}</h2></div>
    </div>

    <div class="card" style="margin-top:20px; overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Total Employees</th>
                    <th>Active Employees</th>
                    <th>Roles</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $department)
                    @php($isProtected = $department->employees_count > 0 || $department->employee_roles_count > 0)
                    <tr>
                        <td>{{ $department->sort_order }}</td>
                        <td><strong>{{ $department->name }}</strong><br><small style="color:#94a3b8;">{{ $department->description ?: $department->slug }}</small></td>
                        <td><span class="badge {{ $department->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ ucfirst($department->status) }}</span></td>
                        <td>{{ $department->employees_count }}</td>
                        <td>{{ $department->active_employees_count }}</td>
                        <td>{{ $department->employee_roles_count }}</td>
                        <td style="white-space:nowrap;">
                            @if(auth()->user()->hasPermission('departments.manage'))
                                <a class="btn" href="/admin/departments/{{ $department->id }}/edit">Edit</a>
                                @if($isProtected)
                                    <span class="badge badge-neutral" title="Departments with employees or roles should be set inactive instead of deleted.">Protected</span>
                                @else
                                    <form method="POST" action="/admin/departments/{{ $department->id }}" style="display:inline" onsubmit="return confirm('Delete this department?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Delete</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No departments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
