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
        <p>Title<br><input type="text" name="title" value="{{ old('title', $notice?->title) }}" required></p>
        <p>Category<br>
            <select name="category" required>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" {{ old('category', $notice?->category ?? 'general') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p>Description<br><textarea name="description" required>{{ old('description', $notice?->description) }}</textarea></p>
        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>
