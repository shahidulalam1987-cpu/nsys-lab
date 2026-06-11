@extends('layouts.admin')

@section('content')
    <h1>Add Daily Performance</h1>
    <a class="btn" href="/admin/daily-reports">Back to Daily Performance</a>

    @include('admin.daily-reports.partials.form', [
        'action' => '/admin/daily-reports',
        'button' => 'Save Performance',
        'isEdit' => false,
    ])
@endsection
