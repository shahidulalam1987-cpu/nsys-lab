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
@if($account)
    <div style="grid-column:1/-1; padding:16px; border:1px solid var(--line); background:rgba(255,255,255,.04); border-radius:8px;">
        <p style="margin:0 0 5px;">Current Balance</p>
        <h2 id="current-balance" data-balance="{{ (float) $account->current_balance }}" data-currency="{{ $account->currency }}" style="margin:0;">{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</h2>
        <small style="color:var(--muted);">Current ledger balance. This value cannot be edited directly.</small>
    </div>
    <fieldset style="grid-column:1/-1; border:0; padding:0; margin:0;">
        <legend style="margin-bottom:8px;">Adjustment Type</legend>
        <label style="margin-right:18px;"><input type="radio" name="adjustment_type" value="credit" @checked(old('adjustment_type', 'credit') === 'credit') required> Credit (Increase Balance)</label>
        <label><input type="radio" name="adjustment_type" value="debit" @checked(old('adjustment_type') === 'debit') required> Debit (Decrease Balance)</label>
    </fieldset>
    <label>Adjustment Amount<br><input id="adjustment-amount" type="number" min="0.01" step="0.01" name="adjustment_amount" value="{{ old('adjustment_amount') }}" required><br><small style="color:var(--muted);">Enter only the amount to add or subtract.</small></label>
    <label>Balance Adjustment Reason<br><input id="adjustment-reason" type="text" minlength="5" maxlength="1000" name="adjustment_reason" value="{{ old('adjustment_reason') }}" placeholder="Cash Deposit, Bank Correction..." required><br><small style="color:var(--muted);">Every adjustment creates an immutable Finance Ledger record.</small></label>
    <div style="grid-column:1/-1; display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; padding:16px; border:1px solid var(--line); border-radius:8px;">
        <div><small>Current Balance</small><strong id="preview-current" style="display:block;"></strong></div>
        <div><small>Adjustment</small><strong id="preview-adjustment" style="display:block;"></strong></div>
        <div><small>New Balance</small><strong id="preview-new" style="display:block;"></strong></div>
    </div>
@else
    <label>Opening Balance<br><input type="number" step="0.01" name="current_balance" value="{{ old('current_balance', 0) }}" required></label>
@endif
<label>Status<br>
    <select name="status" required>
        @foreach($statuses as $value => $label)
            <option value="{{ $value }}" {{ old('status', $account?->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</label>
<label style="grid-column:1/-1;">Note<br><textarea name="note" rows="2" style="width:100%;">{{ old('note', $account?->note) }}</textarea></label>
