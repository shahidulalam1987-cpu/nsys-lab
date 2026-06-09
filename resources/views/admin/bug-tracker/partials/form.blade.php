<div class="card">
    <form method="POST" action="{{ $action }}">
        @csrf

        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
            <label>
                Module
                <input type="text" name="module" value="{{ old('module', $bug->module) }}" placeholder="Employee Portal" required>
            </label>

            <label>
                Title
                <input type="text" name="title" value="{{ old('title', $bug->title) }}" placeholder="Short bug title" required>
            </label>

            <label>
                Priority
                <select name="priority" required>
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $bug->priority) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Status
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $bug->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Reported By
                <input type="text" name="reported_by" value="{{ old('reported_by', $bug->reported_by) }}" placeholder="Reporter name">
            </label>

            <label>
                Assigned To
                <input type="text" name="assigned_to" value="{{ old('assigned_to', $bug->assigned_to) }}" placeholder="Assignee name">
            </label>
        </div>

        <label style="display:block;margin-top:14px;">
            Description
            <textarea name="description" rows="5" style="width:100%;" placeholder="What happened? Steps, expected result, actual result.">{{ old('description', $bug->description) }}</textarea>
        </label>

        <label style="display:block;margin-top:14px;">
            Fixed Note
            <textarea name="fixed_note" rows="4" style="width:100%;" placeholder="What was fixed or verified?">{{ old('fixed_note', $bug->fixed_note) }}</textarea>
        </label>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;">
            <a href="/admin/bug-tracker">Cancel</a>
            <button class="btn" type="submit">{{ $buttonText }}</button>
        </div>
    </form>
</div>

@if($errors->any())
    <div class="card" style="border-color:#ef4444;color:#fecaca;">
        {{ $errors->first() }}
    </div>
@endif
