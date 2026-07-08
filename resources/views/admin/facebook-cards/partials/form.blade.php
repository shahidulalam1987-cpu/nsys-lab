@csrf

<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
    <label>Card Name<br>
        <input type="text" name="card_name" value="{{ old('card_name', $card->card_name) }}" required>
    </label>
    <label>Card Type<br>
        <input type="text" name="card_type" value="{{ old('card_type', $card->card_type) }}" placeholder="Visa, MasterCard, Virtual Card">
    </label>
    <label>Last 4 Digit<br>
        <input type="text" name="card_last_four" value="{{ old('card_last_four', $card->card_last_four) }}" maxlength="4">
    </label>
    <label>Provider<br>
        <select name="provider">
            <option value="">Select Provider</option>
            @php($providers = collect(['RedotPay', 'Tevau', 'Other'])->when($card->provider && ! in_array($card->provider, ['RedotPay', 'Tevau', 'Other'], true), fn($items) => $items->prepend($card->provider))->unique())
            @foreach($providers as $provider)
                <option value="{{ $provider }}" @selected(old('provider', $card->provider) === $provider)>{{ $provider }}</option>
            @endforeach
        </select>
    </label>
    <label>Current Balance<br>
        <input type="number" step="0.01" name="current_balance" value="{{ old('current_balance', $card->current_balance ?? 0) }}" required>
    </label>
    <label>Currency<br>
        <input type="text" value="USD" readonly>
    </label>
    @if($card->exists)
        <label>Balance Adjustment Reason<br><input type="text" name="adjustment_reason" value="{{ old('adjustment_reason') }}" placeholder="Required when balance changes"></label>
    @endif
    <label>Assigned Ad Account<br>
        <select name="ad_account_id">
            <option value="">Not Assigned</option>
            @foreach($adAccounts as $adAccount)
                <option value="{{ $adAccount->id }}" @selected((string) old('ad_account_id', $card->ad_account_id) === (string) $adAccount->id)>
                    {{ $adAccount->ad_account_name }} ({{ $adAccount->ad_account_id }})
                </option>
            @endforeach
        </select>
    </label>
    <label>Status<br>
        <select name="status" required>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $card->status ?: 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
</div>

<label style="display:block;margin-top:12px;">Notes<br>
    <textarea name="notes" rows="4" style="width:100%;">{{ old('notes', $card->notes) }}</textarea>
</label>

@if($errors->any())
    <div class="card" style="border-color:#ef4444;color:#fecaca;">
        {{ $errors->first() }}
    </div>
@endif

<button class="btn" type="submit">Save Card</button>
<a href="/admin/facebook-cards">Cancel</a>
