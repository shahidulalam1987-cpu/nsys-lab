@extends('layouts.admin')

@section('content')
    <h1>Pending Client Payments</h1>

    <a class="btn" href="/admin/salary-payments">Client Payment History</a>

    @include('admin.salary-payments.partials.table', ['payments' => $payments])
@endsection
