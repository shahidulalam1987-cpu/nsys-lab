@extends('layouts.admin')

@section('title', 'Edit Document')
@section('page_title', 'Edit Document')
@section('page_description', 'Update document metadata without changing existing file history.')

@section('content')
<div class="content-card">
    <form method="POST" action="/admin/documents/{{ $document->id }}/update" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
        @csrf
        @include('admin.documents.partials.form', ['document' => $document, 'metadataOnly' => true])
        <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;gap:10px;">
            <a class="btn" href="/admin/documents/{{ $document->id }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Changes</button>
        </div>
    </form>
</div>
@endsection
