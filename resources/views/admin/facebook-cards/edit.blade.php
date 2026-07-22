@extends('layouts.admin')

@section('content')
    <h1>Edit Card</h1>
    <p>Update card details, assigned ad account, and balance adjustment reason when the balance changes.</p>

    <div class="card">
        <form method="POST" action="/admin/facebook-cards/{{ $card->id }}/update">
            @include('admin.facebook-cards.partials.form')
        </form>
    </div>
@endsection
