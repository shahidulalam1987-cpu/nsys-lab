@extends('layouts.admin')

@section('content')
    <h1>Edit BM</h1>
    <a class="btn" href="/admin/business-managers/{{ $businessManager->id }}">Back to BM Details</a>

    @include('admin.business-managers.partials.form', [
        'action' => '/admin/business-managers/' . $businessManager->id . '/update',
        'button' => 'Update BM',
    ])
@endsection
