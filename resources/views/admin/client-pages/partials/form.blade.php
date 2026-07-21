@if ($errors->any())
    <div class="card" style="color:#ef4444; margin-top:20px;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card" style="margin-top:20px;">
    <form method="POST" action="{{ $action }}">
        @csrf

        <p>Client<br>
            <select name="client_id" required>
                <option value="">Select Client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id', $page?->client_id) == $client->id ? 'selected' : '' }}>
                        {{ $client->company_name }}
                    </option>
                @endforeach
            </select>
        </p>
        <p>BM<br>
            <select name="business_manager_id">
                <option value="">No BM</option>
                @foreach($businessManagers as $bm)
                    <option value="{{ $bm->id }}" {{ old('business_manager_id', $page?->business_manager_id) == $bm->id ? 'selected' : '' }}>
                        {{ $bm->bm_name }}
                    </option>
                @endforeach
            </select>
        </p>
        <p>Ad Account<br>
            <select name="ad_account_id">
                <option value="">No Ad Account</option>
                @foreach($adAccounts as $account)
                    <option value="{{ $account->id }}" data-bm-id="{{ $account->business_manager_id }}" {{ old('ad_account_id', $page?->ad_account_id) == $account->id ? 'selected' : '' }}>
                        {{ $account->ad_account_name }} - {{ $account->ad_account_id }}
                    </option>
                @endforeach
            </select>
        </p>

        <p>Page Name<br><input type="text" name="page_name" value="{{ old('page_name', $page?->page_name) }}" required></p>
        <p>Page ID<br><input type="text" name="page_id" value="{{ old('page_id', $page?->page_id) }}"></p>
        <p>Page URL<br><input type="url" name="page_url" value="{{ old('page_url', $page?->page_url) }}" placeholder="https://"></p>
        <p>Platform<br>
            <select name="platform" required>
                @foreach($platforms as $platform)
                    <option value="{{ $platform }}" {{ old('platform', $page?->platform ?? 'Facebook') === $platform ? 'selected' : '' }}>{{ $platform }}</option>
                @endforeach
            </select>
        </p>
        <p>Status<br>
            <select name="status" required>
                @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $page?->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </p>
        <p style="color:var(--muted);">
            Page relationships are used by campaigns, assignments, work status, and performance reporting.
        </p>
        <p>Note<br><textarea name="note">{{ old('note', $page?->note) }}</textarea></p>

        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>
