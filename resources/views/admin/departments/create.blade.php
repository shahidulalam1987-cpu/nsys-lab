@extends('layouts.admin')

@section('content')
    <h1>Add Department</h1>
    @include('admin.departments.partials.form', ['department' => null, 'action' => '/admin/departments', 'button' => 'Save Department'])
@endsection
