@extends('layouts.admin')

@section('content')
    <h1>Datasets</h1>
    <p>Meta dataset mapping for ad account, client, and page relationships.</p>

    <div class="card">
        <form method="POST" action="/admin/datasets" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Dataset Name<br><input name="dataset_name" required></label>
            <label>Dataset ID<br><input name="dataset_id" required></label>
            <label>Platform<br><input name="platform" value="Meta" required></label>
            <label>Ad Account<br><select name="ad_account_id"><option value="">None</option>@foreach($adAccounts as $account)<option value="{{ $account->id }}">{{ $account->ad_account_name }}</option>@endforeach</select></label>
            <label>Client<br><select name="client_id"><option value="">None</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->company_name }}</option>@endforeach</select></label>
            <label>Page<br><select name="client_page_id"><option value="">None</option>@foreach($pages as $page)<option value="{{ $page->id }}">{{ $page->page_name }}</option>@endforeach</select></label>
            <label>Status<br><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <label style="grid-column:span 2;">Notes<br><input name="notes"></label>
            <button class="btn" type="submit">Add Dataset</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Dataset</th><th>ID</th><th>Ad Account</th><th>Client</th><th>Page</th><th>Status</th></tr>
            @forelse($datasets as $dataset)
                <tr>
                    <td>{{ $dataset->dataset_name }}<br><small>{{ $dataset->platform }}</small></td>
                    <td>{{ $dataset->dataset_id }}</td>
                    <td>{{ $dataset->adAccount?->ad_account_name ?: '-' }}</td>
                    <td>{{ $dataset->client?->company_name ?: '-' }}</td>
                    <td>{{ $dataset->page?->page_name ?: '-' }}</td>
                    <td><span class="badge">{{ ucfirst($dataset->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6">No datasets found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
