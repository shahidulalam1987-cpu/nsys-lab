@extends('layouts.admin')

@section('content')
    <h1>Edit Pixel/Dataset</h1>
    <a class="btn" href="/admin/datasets">Back to Pixels & Datasets</a>

    @include('admin.datasets.partials.form', [
        'action' => '/admin/datasets/' . $dataset->id . '/update',
        'button' => 'Update Pixel/Dataset',
    ])
@endsection
