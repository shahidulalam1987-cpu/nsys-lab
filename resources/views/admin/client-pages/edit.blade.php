@extends('layouts.admin')

@section('content')
    <h1>Edit Client Page</h1>
    <a class="btn" href="/admin/client-pages">Back to Pages</a>

    @include('admin.client-pages.partials.form', [
        'action' => '/admin/client-pages/' . $page->id . '/update',
        'button' => 'Update Page',
    ])
@endsection
