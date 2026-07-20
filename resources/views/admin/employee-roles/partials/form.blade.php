@if($errors->any())
    <div class="card" style="color:#ef4444; margin-top:20px;"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card" style="margin-top:20px; max-width:760px;">
    <form method="POST" action="{{ $action }}">
        @csrf
        @if($employeeRole) @method('PUT') @endif
        <p>Role Name<br><input type="text" name="name" value="{{ old('name', $employeeRole?->name) }}" required></p>
        <p>Department<br>
            <select name="department_id">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ (int) old('department_id', $employeeRole?->department_id) === $department->id ? 'selected' : '' }}>{{ $department->name }}{{ $department->status === 'inactive' ? ' (Inactive)' : '' }}</option>
                @endforeach
            </select>
            <br><small style="color:#94a3b8;">Use All Departments for shared roles, or choose a department to prioritize this role in employee forms.</small>
        </p>
        <p>Description<br><textarea name="description">{{ old('description', $employeeRole?->description) }}</textarea></p>
        <p>Status<br><select name="status" required><option value="active" {{ old('status', $employeeRole?->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option><option value="inactive" {{ old('status', $employeeRole?->status) === 'inactive' ? 'selected' : '' }}>Inactive</option></select>
            <br><small style="color:#94a3b8;">Inactive roles stay visible for assigned employees but should not be used for new employees.</small>
        </p>
        <p>Sort Order<br><input type="number" name="sort_order" min="0" value="{{ old('sort_order', $employeeRole?->sort_order ?? 0) }}" required></p>
        <div style="display:flex; justify-content:flex-end; gap:10px;"><a class="btn" href="/admin/employee-roles">Cancel</a><button class="btn" type="submit">{{ $button }}</button></div>
    </form>
</div>
