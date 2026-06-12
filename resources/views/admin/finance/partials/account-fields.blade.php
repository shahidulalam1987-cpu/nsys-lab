<label>Account Type<br>
    <select name="account_type" required>
        <option value="">Select Type</option>
        @foreach($types as $value => $label)
            <option value="{{ $value }}" {{ old('account_type', $account?->account_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</label>
<label>Account Name<br><input type="text" name="account_name" value="{{ old('account_name', $account?->account_name) }}" required></label>
<label>Provider / Bank Name<br><input type="text" name="provider_name" value="{{ old('provider_name', $account?->provider_name) }}"></label>
<label>Account Number<br><input type="text" name="account_number" value="{{ old('account_number', $account?->account_number) }}"></label>
<label>Currency<br>
    <select name="currency" required>
        @foreach($currencies as $value => $label)
            <option value="{{ $value }}" {{ old('currency', $account?->currency ?? 'BDT') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</label>
<label>Current Balance<br><input type="number" step="0.01" name="current_balance" value="{{ old('current_balance', $account?->current_balance ?? 0) }}" required></label>
<label>Status<br>
    <select name="status" required>
        @foreach($statuses as $value => $label)
            <option value="{{ $value }}" {{ old('status', $account?->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</label>
<label style="grid-column:1/-1;">Note<br><textarea name="note" rows="2" style="width:100%;">{{ old('note', $account?->note) }}</textarea></label>
