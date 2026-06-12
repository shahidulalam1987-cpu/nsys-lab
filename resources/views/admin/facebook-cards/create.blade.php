@extends('layouts.admin')

@section('content')
    <h1>Add Card</h1>
    <p>Track card balance for Facebook ad account billing and alert monitoring.</p>

    <div class="card">
        <form method="POST" action="/admin/facebook-cards">
            @include('admin.facebook-cards.partials.form')
        </form>
    </div>
@endsection
