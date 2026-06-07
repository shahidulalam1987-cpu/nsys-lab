@extends('layouts.admin')

@section('content')
    <h1>Pending Client Payments</h1>

    <a class="btn" href="/admin/salary-payments/create">Receive Client Payment</a>
    <a class="btn" href="/admin/salary-payments">Client Payment History</a>

    @include('admin.salary-payments.partials.table', [
        'payments' => $payments,
        'mode' => 'pending',
        'emptyMessage' => 'No pending client payments found.',
    ])
@endsection
