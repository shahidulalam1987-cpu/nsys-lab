@if ($errors->any())
    <div class="card" style="color:#ef4444; margin-top:20px;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="card" style="margin-top:20px;">
    <form method="POST" action="{{ $action }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:start;">
            <label>
                Title<br>
                <input type="text" name="title" value="{{ old('title', $notice?->title) }}" required style="width:100%;margin-left:0;">
            </label>
            <label>
                Category<br>
                <select name="category" required style="width:100%;margin-left:0;">
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ old('category', $notice?->category ?? 'general') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <p>Description<br><textarea name="description" required style="width:100%;min-height:160px;margin-left:0;">{{ old('description', $notice?->description) }}</textarea></p>
        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>
