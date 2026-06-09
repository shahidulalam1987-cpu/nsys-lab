@extends('layouts.admin')

@section('content')
    <h1>Edit Bug {{ $bug->bug_id }}</h1>
    <p>Update priority, status, assignment, or fixed notes for this QA issue.</p>

    @include('admin.bug-tracker.partials.form', [
        'action' => '/admin/bug-tracker/' . $bug->id . '/update',
        'buttonText' => 'Update Bug',
    ])
@endsection
