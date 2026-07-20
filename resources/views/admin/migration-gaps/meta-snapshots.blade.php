@extends('layouts.admin')

@section('content')
    <h1>Meta Spend Snapshots</h1>
    <p>Manual/future API snapshots for Meta spend without changing approved daily performance reports.</p>

    <div class="card">
        <form method="POST" action="/admin/meta-spend-snapshots" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Campaign<br><select name="campaign_id"><option value="">None</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}">{{ $campaign->campaign_name }}</option>@endforeach</select></label>
            <label>Date<br><input type="date" name="snapshot_date" value="{{ now()->toDateString() }}" required></label>
            <label>Source<br><input name="source" value="manual" required></label>
            <label>Spend USD<br><input type="number" step="0.01" name="spend_usd" required></label>
            <label>Orders<br><input type="number" name="orders" value="0"></label>
            <button class="btn" type="submit">Add Snapshot</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Date</th><th>Campaign</th><th>Spend</th><th>Orders</th><th>Source</th></tr>
            @forelse($snapshots as $snapshot)
                <tr>
                    <td>{{ $snapshot->snapshot_date?->toDateString() }}</td>
                    <td>{{ $snapshot->campaign?->campaign_name ?: '-' }}</td>
                    <td>USD {{ number_format((float) $snapshot->spend_usd, 2) }}</td>
                    <td>{{ number_format((int) $snapshot->orders) }}</td>
                    <td>{{ $snapshot->source }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No Meta spend snapshots found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
