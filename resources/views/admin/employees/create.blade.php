@extends('layouts.admin')

@section('content')
    <h1>Add Employee</h1>
    <a class="btn" href="/admin/employees">Back to Employees</a>

    @include('admin.employees.partials.form', [
        'employee' => null,
        'users' => $users,
        'shifts' => $shifts,
        'action' => '/admin/employees',
        'button' => 'Save Employee',
    ])
@endsection
