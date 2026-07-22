@extends('layouts.admin')

@section('title', 'Upload Document')
@section('page_title', 'Upload Document')
@section('page_description', 'Attach a document to an operational module or store it as a general document.')

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
    <form method="POST" action="/admin/documents" enctype="multipart/form-data" class="dms-form-grid">
        @csrf
        @include('admin.documents.partials.form', ['document' => null])
        <div class="dms-form-actions">
            <a class="btn" href="/admin/documents">Cancel</a>
            <button class="btn btn-primary" type="submit">Upload Document</button>
        </div>
    </form>
</div>
@endsection
