@extends('layouts.admin')

@section('content')
    <h1>Provider Fee Tracking</h1>
    <p>Compare Facebook charge against provider deducted amount.</p>

    <div class="card">
        <form method="POST" action="/admin/provider-fees" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Provider<br><select name="payment_provider_id"><option value="">None</option>@foreach($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->name }}</option>@endforeach</select></label>
            <label>Card<br><select name="facebook_card_id"><option value="">None</option>@foreach($cards as $card)<option value="{{ $card->id }}">{{ $card->card_name }}</option>@endforeach</select></label>
            <label>Date<br><input type="date" name="sample_date" value="{{ now()->toDateString() }}" required></label>
            <label>Facebook Charge USD<br><input type="number" step="0.01" name="facebook_charge_usd" required></label>
            <label>Provider Deducted USD<br><input type="number" step="0.01" name="provider_deducted_usd" required></label>
            <label>Notes<br><input name="notes"></label>
            <button class="btn" type="submit">Add Fee Sample</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Date</th><th>Provider</th><th>Card</th><th>Facebook Charge</th><th>Provider Deducted</th><th>Fee</th><th>Fee %</th></tr>
            @forelse($fees as $fee)
                <tr>
                    <td>{{ $fee->sample_date?->toDateString() }}</td>
                    <td>{{ $fee->provider?->name ?: '-' }}</td>
                    <td>{{ $fee->card?->card_name ?: '-' }}</td>
                    <td>USD {{ number_format((float) $fee->facebook_charge_usd, 2) }}</td>
                    <td>USD {{ number_format((float) $fee->provider_deducted_usd, 2) }}</td>
                    <td>USD {{ number_format((float) $fee->fee_amount_usd, 2) }}</td>
                    <td>{{ number_format((float) $fee->fee_percentage, 2) }}%</td>
                </tr>
            @empty
                <tr><td colspan="7">No fee samples found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
