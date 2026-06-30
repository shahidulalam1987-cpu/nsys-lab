@extends('layouts.admin')

@section('content')
    <h1>Edit Role</h1>
    @include('admin.employee-roles.partials.form', ['employeeRole' => $employeeRole, 'action' => '/admin/employee-roles/' . $employeeRole->id, 'button' => 'Update Role'])
@endsection
