@extends('layouts.admin')

@section('content')
    <h1>Add Bug</h1>
    <p>Record an internal QA issue. Bug ID will be generated automatically.</p>

    @include('admin.bug-tracker.partials.form', [
        'action' => '/admin/bug-tracker',
        'buttonText' => 'Save Bug',
    ])
@endsection
