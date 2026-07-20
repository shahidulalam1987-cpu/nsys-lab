@extends('layouts.admin')

@section('content')
    <h1>Ad Account Card Mapping</h1>
    <p>Map payment cards to ad accounts while keeping Card Management as the balance source.</p>

    <div class="card">
        <form method="POST" action="/admin/ad-account-cards" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Ad Account<br><select name="ad_account_id" required>@foreach($adAccounts as $account)<option value="{{ $account->id }}">{{ $account->ad_account_name }}</option>@endforeach</select></label>
            <label>Card<br><select name="facebook_card_id" required>@foreach($cards as $card)<option value="{{ $card->id }}">{{ $card->provider ?: 'Other' }} - {{ $card->card_name }}</option>@endforeach</select></label>
            <label>Status<br><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <label><input type="checkbox" name="is_primary" value="1"> Primary Card</label>
            <label>Mapped From<br><input type="date" name="mapped_from"></label>
            <label>Mapped To<br><input type="date" name="mapped_to"></label>
            <label style="grid-column:span 2;">Notes<br><input name="notes"></label>
            <button class="btn" type="submit">Add Mapping</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Ad Account</th><th>Card</th><th>Primary</th><th>Status</th><th>Mapped From</th><th>Mapped To</th></tr>
            @forelse($mappings as $mapping)
                <tr>
                    <td>{{ $mapping->adAccount?->ad_account_name ?: '-' }}</td>
                    <td>{{ $mapping->card?->card_name ?: '-' }}</td>
                    <td>{{ $mapping->is_primary ? 'Yes' : 'No' }}</td>
                    <td><span class="badge">{{ ucfirst($mapping->status) }}</span></td>
                    <td>{{ $mapping->mapped_from?->toDateString() ?: '-' }}</td>
                    <td>{{ $mapping->mapped_to?->toDateString() ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No mappings found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
