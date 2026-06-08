@extends('layouts.admin')

@section('content')
    <h1>Edit Assignment</h1>
    <a class="btn" href="/admin/assignments">Back to Assignment Management</a>

    @include('admin.assignments.partials.form', [
        'assignment' => $assignment,
        'action' => '/admin/assignments/' . $assignment->id . '/update',
        'button' => 'Update Assignment',
    ])
@endsection
