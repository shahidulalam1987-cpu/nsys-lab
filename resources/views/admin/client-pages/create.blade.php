@extends('layouts.admin')

@section('content')
    <h1>Add Client Page</h1>
    <a class="btn" href="/admin/client-pages">Back to Page Management</a>

    @include('admin.client-pages.partials.form', [
        'action' => '/admin/client-pages',
        'button' => 'Save Page',
    ])
@endsection
