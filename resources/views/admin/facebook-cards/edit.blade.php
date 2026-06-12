@extends('layouts.admin')

@section('content')
    <h1>Edit Card</h1>
    <p>Update card details and assigned ad account.</p>

    <div class="card">
        <form method="POST" action="/admin/facebook-cards/{{ $card->id }}/update">
            @include('admin.facebook-cards.partials.form')
        </form>
    </div>
@endsection
