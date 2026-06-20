<label>Loan Type<br>
    <select name="loan_type" required>
        <option value="">Select Type</option>
        @foreach($types as $value => $label)
            <option value="{{ $value }}" {{ old('loan_type', $loan?->loan_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</label>
<label>Finance Account<br>
    <select name="finance_account_id" required>
        <option value="">Select BDT Account</option>
        @foreach($accounts as $account)
            <option value="{{ $account->id }}" @selected((string) old('finance_account_id', $loan?->finance_account_id) === (string) $account->id)>{{ $account->account_name }} - BDT {{ number_format((float) $account->current_balance, 2) }}</option>
        @endforeach
    </select>
</label>
<label>Person / Company Name<br><input type="text" name="person_company_name" value="{{ old('person_company_name', $loan?->person_company_name) }}" required></label>
<label>Amount<br><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $loan?->amount) }}" required></label>
<label>Loan Date<br><input type="date" name="loan_date" value="{{ old('loan_date', $loan?->loan_date?->toDateString() ?? now()->toDateString()) }}" required></label>
<label>Due Date<br><input type="date" name="due_date" value="{{ old('due_date', $loan?->due_date?->toDateString()) }}"></label>
@if($loan)
    <label>Paid Amount<br><input type="number" step="0.01" value="{{ $loan->paid_amount }}" readonly></label>
@endif
<label style="grid-column:1/-1;">Note<br><textarea name="note" rows="2" style="width:100%;">{{ old('note', $loan?->note) }}</textarea></label>
