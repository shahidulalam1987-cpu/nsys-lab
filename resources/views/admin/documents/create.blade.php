@extends('layouts.admin')

@section('title', 'Upload Document')
@section('page_title', 'Upload Document')
@section('page_description', 'Attach a document to an operational module or store it as a general document.')

@section('content')
<div class="content-card">
    <form method="POST" action="/admin/documents" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
        @csrf
        @include('admin.documents.partials.form', ['document' => null])
        <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;gap:10px;">
            <a class="btn" href="/admin/documents">Cancel</a>
            <button class="btn btn-primary" type="submit">Upload Document</button>
        </div>
    </form>
</div>
@endsection
