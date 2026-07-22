@extends('layouts.admin')

@section('title', 'Document Management')
@section('page_title', 'Document Management')
@section('page_description', 'Central repository for operational documents, attachments, and version history.')

@section('content')
@php($canManageDocuments = app(\App\Services\DocumentManagementService::class)->canManage(auth()->user()))
<style>
    .dms-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; margin-bottom:16px; }
    .dms-card { background:#111827; border:1px solid #263244; border-radius:8px; padding:14px; }
    .dms-filter { display:grid; grid-template-columns:2fr repeat(4, minmax(130px, 1fr)) auto; gap:10px; align-items:end; }
    .dms-table-wrap { overflow-x:auto; }
    .dms-title { color:#e5edff; font-weight:700; }
    .dms-muted { color:#94a3b8; font-size:12px; }
    @media (max-width: 960px) { .dms-filter { grid-template-columns:1fr; } }
</style>

<div class="dms-grid">
    <div class="dms-card"><div class="dms-muted">Visible Documents</div><strong>{{ number_format($documents->total()) }}</strong></div>
    <div class="dms-card"><div class="dms-muted">Categories</div><strong>{{ count($categories) }}</strong></div>
    <div class="dms-card"><div class="dms-muted">Owner Modules</div><strong>{{ count($ownerModules) }}</strong></div>
    <div class="dms-card"><div class="dms-muted">Upload Formats</div><strong>PDF, DOCX, XLSX, PNG, JPG, ZIP</strong></div>
</div>

<div class="content-card">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:14px;">
        <div>
            <h3 style="margin:0;">Documents</h3>
            <p class="dms-muted" style="margin:4px 0 0;">Search, filter, preview, download, archive, and restore central documents.</p>
        </div>
        @if($canManageDocuments)
            <a class="btn btn-primary" href="/admin/documents/create">Upload Document</a>
        @endif
    </div>

    <form method="GET" class="dms-filter">
        <div>
            <label>Search</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Title, description, file name">
        </div>
        <div>
            <label>Category</label>
            <select name="category">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                <option value="">All Status</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Owner Module</label>
            <select name="owner_module">
                <option value="">All Modules</option>
                @foreach($ownerModules as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['owner_module'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Tag</label>
            <input type="text" name="tag" value="{{ $filters['tag'] ?? '' }}" placeholder="tag">
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn" href="/admin/documents">Reset</a>
        </div>
    </form>
</div>

<div class="content-card">
    <div class="dms-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Category</th>
                    <th>Owner</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td>
                            <a class="dms-title" href="/admin/documents/{{ $document->id }}">{{ $document->title }}</a>
                            <div class="dms-muted">{{ $document->original_file_name ?: '-' }}</div>
                        </td>
                        <td>{{ $document->categoryLabel() }}</td>
                        <td>
                            {{ $ownerModules[$document->owner_module] ?? 'General' }}
                            @if($document->owner_record_id)
                                <div class="dms-muted">#{{ $document->owner_record_id }}</div>
                            @endif
                        </td>
                        <td>{{ strtoupper((string) $document->file_type) }}</td>
                        <td>{{ $document->fileSizeLabel() }}</td>
                        <td>v{{ $document->version }}</td>
                        <td><span class="badge {{ $document->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ $document->statusLabel() }}</span></td>
                        <td>{{ $document->uploaded_at?->format('d M Y') ?: $document->created_at?->format('d M Y') }}</td>
                        <td style="white-space:nowrap;">
                            <a class="btn btn-sm" href="/admin/documents/{{ $document->id }}">View</a>
                            <a class="btn btn-sm" href="/admin/documents/{{ $document->id }}/download">Download</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center">No documents found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px;">{{ $documents->links() }}</div>
</div>
@endsection
