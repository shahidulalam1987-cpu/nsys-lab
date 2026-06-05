@extends('layouts.admin')

@section('content')
    <h1>Edit Employee</h1>
    <a class="btn" href="/admin/employees/{{ $employee->id }}">Back to Profile</a>

    @include('admin.employees.partials.form', [
        'employee' => $employee,
        'users' => $users,
        'action' => '/admin/employees/' . $employee->id . '/update',
        'button' => 'Update Employee',
    ])
@endsection
