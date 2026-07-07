@extends('layouts.admin')

@section('title', 'Document Details')
@section('page_title', 'Document Details')
@section('page_description', 'Document metadata, version history, and audit trail.')

@section('content')
<style>
    .dms-detail-grid { display:grid; grid-template-columns:2fr 1fr; gap:16px; }
    .dms-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .dms-meta div { background:#0f172a; border:1px solid #263244; border-radius:8px; padding:12px; }
    .dms-muted { color:#94a3b8; font-size:12px; }
    @media (max-width: 960px) { .dms-detail-grid, .dms-meta { grid-template-columns:1fr; } }
</style>

<div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:14px;">
    <a class="btn" href="/admin/documents">Back to Documents</a>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn" href="/admin/documents/{{ $document->id }}/download">Download</a>
        @if(in_array(strtolower((string) $document->file_type), ['pdf','png','jpg','jpeg'], true))
            <a class="btn" href="/admin/documents/{{ $document->id }}/preview" target="_blank">Preview</a>
        @endif
        <a class="btn" href="/admin/documents/{{ $document->id }}/edit">Edit</a>
        @if($document->status === 'active')
            <form method="POST" action="/admin/documents/{{ $document->id }}/archive" onsubmit="return confirm('Archive this document?')">@csrf<button class="btn btn-warning" type="submit">Archive</button></form>
        @else
            <form method="POST" action="/admin/documents/{{ $document->id }}/restore">@csrf<button class="btn btn-primary" type="submit">Restore</button></form>
        @endif
    </div>
</div>

<div class="dms-detail-grid">
    <div class="content-card">
        <h3 style="margin-top:0;">{{ $document->title }}</h3>
        <p class="dms-muted">{{ $document->description ?: 'No description added.' }}</p>
        <div class="dms-meta">
            <div><span class="dms-muted">Category</span><br>{{ $document->categoryLabel() }}</div>
            <div><span class="dms-muted">Status</span><br>{{ $document->statusLabel() }}</div>
            <div><span class="dms-muted">Owner Module</span><br>{{ \App\Models\ManagedDocument::OWNER_MODULES[$document->owner_module] ?? 'General' }}</div>
            <div><span class="dms-muted">Owner Record</span><br>{{ $document->owner_record_id ? '#'.$document->owner_record_id : '-' }}</div>
            <div><span class="dms-muted">File</span><br>{{ $document->original_file_name ?: '-' }}</div>
            <div><span class="dms-muted">Size</span><br>{{ $document->fileSizeLabel() }}</div>
            <div><span class="dms-muted">Version</span><br>v{{ $document->version }}</div>
            <div><span class="dms-muted">Expiry</span><br>{{ $document->expiry_date?->format('d M Y') ?: '-' }}</div>
        </div>
    </div>

    <div class="content-card">
        <h3 style="margin-top:0;">Upload New Version</h3>
        <form method="POST" action="/admin/documents/{{ $document->id }}/version" enctype="multipart/form-data">
            @csrf
            <label>File</label>
            <input type="file" name="document" accept=".pdf,.docx,.xlsx,.png,.jpg,.jpeg,.zip" required>
            <label style="margin-top:10px;">Change Note</label>
            <textarea name="change_note" rows="3"></textarea>
            <button class="btn btn-primary" type="submit" style="margin-top:10px;">Upload Version</button>
        </form>
    </div>
</div>

<div class="content-card">
    <h3 style="margin-top:0;">Version History</h3>
    <table>
        <thead><tr><th>Version</th><th>File</th><th>Size</th><th>Uploaded By</th><th>Date</th><th>Note</th></tr></thead>
        <tbody>
            @foreach($document->versions as $version)
                <tr>
                    <td>v{{ $version->version }}</td>
                    <td>{{ $version->original_file_name ?: '-' }}</td>
                    <td>{{ number_format(($version->file_size ?: 0) / 1024, 1) }} KB</td>
                    <td>{{ $version->uploader?->name ?: '-' }}</td>
                    <td>{{ $version->created_at?->format('d M Y h:i A') }}</td>
                    <td>{{ $version->change_note ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="content-card">
    <h3 style="margin-top:0;">Audit Trail</h3>
    <table>
        <thead><tr><th>Date</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
        <tbody>
            @forelse($document->audits as $audit)
                <tr>
                    <td>{{ $audit->created_at?->format('d M Y h:i A') }}</td>
                    <td>{{ $audit->user?->name ?: '-' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $audit->action)) }}</td>
                    <td>{{ $audit->description }}</td>
                    <td>{{ $audit->ip_address ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No audit entries found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
