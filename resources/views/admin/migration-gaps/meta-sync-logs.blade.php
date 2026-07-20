@extends('layouts.admin')

@section('content')
    <h1>Meta Sync Logs</h1>
    <p>Future-ready Meta API sync history.</p>

    <div class="card">
        <form method="POST" action="/admin/meta-sync-logs" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            @csrf
            <label>Sync Type<br><input name="sync_type" value="spend" required></label>
            <label>Status<br><select name="status"><option value="pending">Pending</option><option value="success">Success</option><option value="failed">Failed</option></select></label>
            <label>Records<br><input type="number" name="records_processed" value="0"></label>
            <label>Started At<br><input type="datetime-local" name="started_at"></label>
            <label>Finished At<br><input type="datetime-local" name="finished_at"></label>
            <label>Message<br><input name="message"></label>
            <button class="btn" type="submit">Add Log</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <tr><th>Created</th><th>Type</th><th>Status</th><th>Started</th><th>Finished</th><th>Records</th><th>Message</th></tr>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->sync_type }}</td>
                    <td><span class="badge">{{ ucfirst($log->status) }}</span></td>
                    <td>{{ $log->started_at?->format('Y-m-d H:i') ?: '-' }}</td>
                    <td>{{ $log->finished_at?->format('Y-m-d H:i') ?: '-' }}</td>
                    <td>{{ number_format((int) $log->records_processed) }}</td>
                    <td>{{ $log->message ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No Meta sync logs found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
