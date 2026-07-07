@php
    $relatedDocuments = app(\App\Services\DocumentManagementService::class)->relatedDocuments($ownerModule, $ownerId, auth()->user(), $limit ?? 5);
@endphp
<div class="content-card">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:10px;">
        <div>
            <h3 style="margin:0;">Related Documents</h3>
            <p class="text-muted" style="margin:4px 0 0;">Recent DMS files attached to this record.</p>
        </div>
        <a class="btn btn-sm" href="/admin/documents/create?owner_module={{ $ownerModule }}&owner_record_id={{ $ownerId }}&category={{ urlencode($category ?? 'General') }}">Upload</a>
    </div>
    <table>
        <thead><tr><th>Title</th><th>Category</th><th>Version</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($relatedDocuments as $doc)
                <tr>
                    <td><a href="/admin/documents/{{ $doc->id }}">{{ $doc->title }}</a><br><span class="text-muted">{{ $doc->original_file_name }}</span></td>
                    <td>{{ $doc->categoryLabel() }}</td>
                    <td>v{{ $doc->version }}</td>
                    <td><a class="btn btn-sm" href="/admin/documents/{{ $doc->id }}/download">Download</a></td>
                </tr>
            @empty
                <tr><td colspan="4">No related documents uploaded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
