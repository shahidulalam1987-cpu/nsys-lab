@extends('layouts.admin')

@section('content')
    <h1>WhatsApp Logs</h1>
    <p>Manual/future integration log for client report delivery.</p>

    <div class="card">
        <form method="POST" action="/admin/whatsapp-logs" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Client<br><select name="client_id"><option value="">None</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->company_name }}</option>@endforeach</select></label>
            <label>Recipient<br><input name="recipient"></label>
            <label>Type<br><input name="message_type" value="daily_report" required></label>
            <label>Status<br><select name="status"><option value="pending">Pending</option><option value="sent">Sent</option><option value="failed">Failed</option></select></label>
            <label>Sent At<br><input type="datetime-local" name="sent_at"></label>
            <label>Response<br><input name="response"></label>
            <label style="grid-column:span 2;">Message<br><input name="message"></label>
            <button class="btn" type="submit">Add Log</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Date</th><th>Client</th><th>Recipient</th><th>Type</th><th>Status</th><th>Sent At</th></tr>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->toDateString() }}</td>
                    <td>{{ $log->client?->company_name ?: '-' }}</td>
                    <td>{{ $log->recipient ?: '-' }}</td>
                    <td>{{ $log->message_type }}</td>
                    <td><span class="badge">{{ ucfirst($log->status) }}</span></td>
                    <td>{{ $log->sent_at?->format('Y-m-d H:i') ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No WhatsApp logs found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
