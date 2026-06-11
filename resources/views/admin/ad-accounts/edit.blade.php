@extends('layouts.admin')

@section('content')
    <h1>Edit Ad Account</h1>
    <a class="btn" href="/admin/ad-accounts/{{ $adAccount->id }}">Back to Ad Account Details</a>

    @include('admin.ad-accounts.partials.form', [
        'action' => '/admin/ad-accounts/' . $adAccount->id . '/update',
        'button' => 'Update Ad Account',
    ])
@endsection
