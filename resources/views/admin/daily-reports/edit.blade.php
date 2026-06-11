@extends('layouts.admin')

@section('content')
    <h1>Edit Daily Performance</h1>
    <a class="btn" href="/admin/daily-reports/{{ $dailyReport->id }}">Back to Performance</a>

    @include('admin.daily-reports.partials.form', [
        'action' => '/admin/daily-reports/' . $dailyReport->id . '/update',
        'button' => 'Update Performance',
        'isEdit' => true,
    ])
@endsection
