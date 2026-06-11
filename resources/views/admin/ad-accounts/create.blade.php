@extends('layouts.admin')

@section('content')
    <h1>Create Ad Account</h1>
    <a class="btn" href="/admin/ad-accounts">Back to Ad Account Management</a>

    @include('admin.ad-accounts.partials.form', [
        'action' => '/admin/ad-accounts',
        'button' => 'Save Ad Account',
    ])
@endsection
