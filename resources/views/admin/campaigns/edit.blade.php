@extends('layouts.admin')

@section('content')
    <h1>Edit Campaign</h1>
    <a class="btn" href="/admin/campaigns/{{ $campaign->id }}">Back to Campaign</a>

    @include('admin.campaigns.partials.form', [
        'action' => '/admin/campaigns/' . $campaign->id . '/update',
        'button' => 'Update Campaign',
    ])
@endsection
