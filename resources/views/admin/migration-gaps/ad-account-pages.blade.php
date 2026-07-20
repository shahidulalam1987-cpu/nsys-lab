@extends('layouts.admin')

@section('content')
    <h1>Ad Account Page Mapping</h1>
    <p>Bridge ad accounts to client pages without changing existing page records.</p>

    <div class="card">
        <form method="POST" action="/admin/ad-account-pages" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Ad Account<br><select name="ad_account_id" required>@foreach($adAccounts as $account)<option value="{{ $account->id }}">{{ $account->ad_account_name }}</option>@endforeach</select></label>
            <label>Client<br><select name="client_id"><option value="">No Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->company_name }}</option>@endforeach</select></label>
            <label>Page<br><select name="client_page_id" required>@foreach($pages as $page)<option value="{{ $page->id }}">{{ $page->page_name }}</option>@endforeach</select></label>
            <label>Status<br><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <label>Mapped From<br><input type="date" name="mapped_from"></label>
            <label>Mapped To<br><input type="date" name="mapped_to"></label>
            <label style="grid-column:span 2;">Notes<br><input name="notes"></label>
            <button class="btn" type="submit">Add Mapping</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Ad Account</th><th>Client</th><th>Page</th><th>Status</th><th>Mapped From</th><th>Mapped To</th></tr>
            @forelse($mappings as $mapping)
                <tr>
                    <td>{{ $mapping->adAccount?->ad_account_name ?: '-' }}</td>
                    <td>{{ $mapping->client?->company_name ?: '-' }}</td>
                    <td>{{ $mapping->page?->page_name ?: '-' }}</td>
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
