<label>Date<br><input type="date" name="expense_date" value="{{ old('expense_date', $expense?->expense_date?->toDateString() ?? now()->toDateString()) }}" required></label>
<label>Person Name<br><input type="text" name="person_name" value="{{ old('person_name', $expense?->person_name) }}" required></label>
<label>Relation<br><input type="text" name="relation" value="{{ old('relation', $expense?->relation) }}"></label>
<label>Expense Category<br>
    <select name="expense_category" required>
        <option value="">Select Category</option>
        @foreach($categories as $value => $label)
            <option value="{{ $value }}" {{ old('expense_category', $expense?->expense_category) === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</label>
<label>Amount<br><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expense?->amount) }}" required></label>
<label>Payment Method<br><input type="text" name="payment_method" value="{{ old('payment_method', $expense?->payment_method) }}"></label>
<label>From Account<br>
    <select name="finance_account_id">
        <option value="">No Account Deduction</option>
        @foreach($accounts as $account)
            <option value="{{ $account->id }}" {{ (string) old('finance_account_id', $expense?->finance_account_id) === (string) $account->id ? 'selected' : '' }}>
                {{ $account->account_name }} - {{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}
            </option>
        @endforeach
    </select>
</label>
<label style="grid-column:1/-1;">Purpose / Note<br><textarea name="note" rows="2" style="width:100%;">{{ old('note', $expense?->note) }}</textarea></label>
