@extends('layouts.admin')

@section('content')
    <h1>Add Card</h1>
    <p>Add a Facebook payment card for Card Management, loads, transactions, and balance monitoring.</p>

    <div class="card">
        <form method="POST" action="/admin/facebook-cards">
            @include('admin.facebook-cards.partials.form')
        </form>
    </div>
@endsection
