@extends('layouts.admin')

@section('content')
    <h1>Create Pixel/Dataset</h1>
    <a class="btn" href="/admin/datasets">Back to Pixels & Datasets</a>

    @include('admin.datasets.partials.form', [
        'action' => '/admin/datasets',
        'button' => 'Save Pixel/Dataset',
    ])
@endsection
