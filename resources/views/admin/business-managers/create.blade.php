@extends('layouts.admin')

@section('content')
    <h1>Create BM</h1>
    <a class="btn" href="/admin/business-managers">Back to BM Management</a>

    @include('admin.business-managers.partials.form', [
        'action' => '/admin/business-managers',
        'button' => 'Save BM',
    ])
@endsection
