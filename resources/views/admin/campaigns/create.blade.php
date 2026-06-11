@extends('layouts.admin')

@section('content')
    <h1>Create Campaign</h1>
    <a class="btn" href="/admin/campaigns">Back to Campaigns</a>

    @include('admin.campaigns.partials.form', [
        'action' => '/admin/campaigns',
        'button' => 'Save Campaign',
    ])
@endsection
