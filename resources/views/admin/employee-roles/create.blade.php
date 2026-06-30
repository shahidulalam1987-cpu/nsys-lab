@extends('layouts.admin')

@section('content')
    <h1>Add Role</h1>
    @include('admin.employee-roles.partials.form', ['employeeRole' => null, 'action' => '/admin/employee-roles', 'button' => 'Save Role'])
@endsection
