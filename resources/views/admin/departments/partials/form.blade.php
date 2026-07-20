@if($errors->any())
    <div class="card" style="color:#ef4444; margin-top:20px;">
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="card" style="margin-top:20px; max-width:760px;">
    <form method="POST" action="{{ $action }}">
        @csrf
        @if($department) @method('PUT') @endif

        <p>Department Name<br><input type="text" name="name" value="{{ old('name', $department?->name) }}" required></p>
        <p>Description<br><textarea name="description">{{ old('description', $department?->description) }}</textarea></p>
        <p>Status<br>
            <select name="status" required>
                <option value="active" {{ old('status', $department?->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $department?->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <br><small style="color:#94a3b8;">Inactive departments remain visible for existing employees and roles, but should not be used for new assignments.</small>
        </p>
        <p>Sort Order<br><input type="number" name="sort_order" min="0" value="{{ old('sort_order', $department?->sort_order ?? 0) }}" required></p>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <a class="btn" href="/admin/departments">Cancel</a>
            <button class="btn" type="submit">{{ $button }}</button>
        </div>
    </form>
</div>
