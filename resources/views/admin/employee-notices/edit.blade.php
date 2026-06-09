@extends('layouts.admin')

@section('content')
    <h1>Edit Notice</h1>
    <a class="btn" href="/admin/employee-notices">Back to Notice Board</a>

    @include('admin.employee-notices.partials.form', [
        'action' => '/admin/employee-notices/' . $notice->id . '/update',
        'button' => 'Update Notice',
    ])
@endsection
