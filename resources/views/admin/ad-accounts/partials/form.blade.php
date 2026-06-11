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
            <label>Ad Account Name<br><input type="text" name="ad_account_name" value="{{ old('ad_account_name', $adAccount?->ad_account_name) }}" required></label>
            <label>Ad Account ID<br><input type="text" name="ad_account_id" value="{{ old('ad_account_id', $adAccount?->ad_account_id) }}" required></label>
            <label>BM<br>
                <select name="business_manager_id" required>
                    <option value="">Select BM</option>
                    @foreach($businessManagers as $bm)
                        <option value="{{ $bm->id }}" @selected(old('business_manager_id', $adAccount?->business_manager_id) == $bm->id)>{{ $bm->bm_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Client<br>
                <select name="client_id">
                    <option value="">No Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $adAccount?->client_id) == $client->id)>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Currency<br><input type="text" value="USD" readonly></label>
            <label>Timezone<br><input type="text" name="timezone" value="{{ old('timezone', $adAccount?->timezone ?? 'Asia/Dhaka') }}" required></label>
            <label>Threshold Amount<br><input type="number" step="0.01" min="0" name="threshold_amount" value="{{ old('threshold_amount', $adAccount?->threshold_amount ?? 0) }}" required></label>
            <label>Current Threshold Usage<br><input type="number" step="0.01" min="0" name="current_threshold_usage" value="{{ old('current_threshold_usage', $adAccount?->current_threshold_usage ?? 0) }}" required></label>
            <label>Current Balance<br><input type="number" step="0.01" name="current_balance" value="{{ old('current_balance', $adAccount?->current_balance ?? 0) }}" required></label>
            <label>Monthly Billing Date<br><input type="number" min="1" max="31" name="monthly_billing_date" value="{{ old('monthly_billing_date', $adAccount?->monthly_billing_date) }}"></label>
            <label>Last Payment Date<br><input type="date" name="last_payment_date" value="{{ old('last_payment_date', $adAccount?->last_payment_date?->toDateString()) }}"></label>
            <label>Payment Method<br><input type="text" name="payment_method" value="{{ old('payment_method', $adAccount?->payment_method) }}"></label>
            <label>Card Last 4 Digit<br><input type="text" name="card_last_four" maxlength="4" value="{{ old('card_last_four', $adAccount?->card_last_four) }}"></label>
            <label>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $adAccount?->status ?? 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <p>Notes<br><textarea name="notes">{{ old('notes', $adAccount?->notes) }}</textarea></p>
        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>
