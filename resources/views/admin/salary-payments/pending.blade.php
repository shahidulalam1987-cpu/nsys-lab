@extends('layouts.admin')

@section('content')
    <h1>Pending Salary Payments</h1>

    <a class="btn" href="/admin/salary-payments">All Salary Payments</a>

    @include('admin.salary-payments.partials.table', ['payments' => $payments])
@endsection
