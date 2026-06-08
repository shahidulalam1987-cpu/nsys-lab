@extends('layouts.admin')

@section('content')
    <h1>Create Assignment</h1>
    <a class="btn" href="/admin/assignments">Back to Assignment Management</a>

    @include('admin.assignments.partials.form', [
        'assignment' => null,
        'action' => '/admin/assignments',
        'button' => 'Save Assignment',
    ])
@endsection
