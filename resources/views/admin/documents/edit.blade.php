@extends('layouts.admin')

@section('title', 'Edit Document')
@section('page_title', 'Edit Document')
@section('page_description', 'Update document metadata without changing existing file history.')

@section('content')
<style>
    .dms-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
    }

    .dms-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        grid-column: 1 / -1;
        flex-wrap: wrap;
    }
</style>

<div class="content-card">
    <form method="POST" action="/admin/documents/{{ $document->id }}/update" class="dms-form-grid">
        @csrf
        @include('admin.documents.partials.form', ['document' => $document, 'metadataOnly' => true])
        <div class="dms-form-actions">
            <a class="btn" href="/admin/documents/{{ $document->id }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Changes</button>
        </div>
    </form>
</div>
@endsection
