@extends('layouts.admin')

@section('content')
    <h1>Edit Department</h1>
    @include('admin.departments.partials.form', ['department' => $department, 'action' => '/admin/departments/' . $department->id, 'button' => 'Update Department'])
@endsection
