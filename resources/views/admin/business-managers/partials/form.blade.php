@if ($errors->any())
    <div class="card" style="color:#ef4444;margin-top:20px;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="card" style="margin-top:20px;">
    <form method="POST" action="{{ $action }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            <label>BM Name<br><input type="text" name="bm_name" value="{{ old('bm_name', $businessManager?->bm_name) }}" required></label>
            <label>BM ID<br><input type="text" name="bm_id" value="{{ old('bm_id', $businessManager?->bm_id) }}" required></label>
            <label>Owner Name<br><input type="text" name="owner_name" value="{{ old('owner_name', $businessManager?->owner_name) }}" required></label>
            <label>Owner Email<br><input type="email" name="owner_email" value="{{ old('owner_email', $businessManager?->owner_email) }}" required></label>
            <label>Verification Status<br>
                <select name="verification_status" required>
                    @foreach($verificationStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('verification_status', $businessManager?->verification_status ?? 'unverified') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $businessManager?->status ?? 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <p>Notes<br><textarea name="notes">{{ old('notes', $businessManager?->notes) }}</textarea></p>
        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>
